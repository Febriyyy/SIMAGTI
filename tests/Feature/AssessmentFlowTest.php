<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Assessment;
use App\Models\Internship;
use App\Models\User;
use App\Support\AssessmentStandard;
use App\Support\Grading;
use Database\Seeders\TestingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Penilaian akhir di akhir periode KP: Company Supervisor mengklik skala nilai standar kampus untuk setiap
 * pertanyaan (hard skill, soft skill, attitude), dosen pembimbing memvalidasi, nilai terkunci.
 * Kaprodi mengatur bobot kategori; admin mengatur pertanyaan, skala nilai, dan huruf mutu.
 */
class AssessmentFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TestingSeeder::class);
    }

    private function user(string $email): User
    {
        return User::where('email', $email)->firstOrFail();
    }

    private function otherLecturer(): User
    {
        return User::create(['name' => 'Dosen Lain', 'email' => 'dosen.lain@kp.test', 'password' => 'password', 'role' => 'dospem', 'is_active' => true]);
    }

    private function internshipOf(string $email): Internship
    {
        return Internship::where('student_id', $this->user($email)->id)->latest('id')->firstOrFail();
    }

    /** Majukan akhir KP supaya masa penilaian terbuka (14 hari sebelum KP berakhir). */
    private function open(Internship $internship, int $daysLeft = 5): Internship
    {
        $internship->update(['end_date' => now()->addDays($daysLeft)->toDateString()]);

        return $internship->fresh();
    }

    /** @param  array<string, list<float>>  $byCategory  kategori => nilai untuk pertanyaan ke-1, ke-2, ... */
    private function scores(array $byCategory): array
    {
        $out = [];
        foreach ($byCategory as $category => $values) {
            foreach (AssessmentStandard::rubric()->aspects->where('category', $category)->values() as $k => $aspect) {
                if (array_key_exists($k, $values)) {
                    $out[$aspect->id] = $values[$k];
                }
            }
        }

        return $out;
    }

    private function full(float $value = 85): array
    {
        return AssessmentStandard::rubric()->aspects->mapWithKeys(fn ($a) => [$a->id => $value])->all();
    }

    public function test_standar_bawaan_tiga_kategori_bobot_dan_skala(): void
    {
        $rubric = AssessmentStandard::rubric();
        $this->assertSame(['hard' => 4, 'soft' => 4, 'attitude' => 6], collect(AssessmentStandard::CATEGORIES)->map(fn ($c, $k) => $rubric->aspects->where('category', $k)->count())->all());
        $this->assertEquals(['hard' => 35, 'soft' => 35, 'attitude' => 30], AssessmentStandard::weights());
        $this->assertSame(['Sangat baik', 'Baik', 'Cukup', 'Kurang', 'Sangat kurang'], array_column(AssessmentStandard::scale(), 'label'));
        $this->assertSame(['A' => 80.0, 'B+' => 74.0, 'B' => 68.0, 'C+' => 62.0, 'C' => 56.0, 'D' => 45.0, 'E' => 0.0], Grading::scale());
    }

    public function test_supervisor_melihat_daftar_dan_form_penilaian_bimbingannya(): void
    {
        $this->actingAs($this->user('supervisor@kp.test'))
            ->get('/penilaian')
            ->assertOk()
            ->assertSee('Rina Putri')
            ->assertSee('Belum dibuka')
            ->assertSee('Menunggu validasi'); // Fajar

        $rina = $this->open($this->internshipOf('mahasiswa@kp.test'));
        $this->get('/penilaian')->assertSee('Perlu dinilai');
        $this->get("/penilaian/{$rina->id}")
            ->assertOk()
            ->assertSee('Hard skill')->assertSee('Soft skill')->assertSee('Attitude')
            ->assertSee('Kualitas hasil kerja')
            ->assertSee('Sangat baik')
            ->assertSee('bobot 35%');
    }

    public function test_penilaian_belum_dibuka_sebelum_akhir_periode(): void
    {
        $rina = $this->internshipOf('mahasiswa@kp.test'); // KP berakhir 2 bulan lagi

        $this->actingAs($this->user('supervisor@kp.test'))
            ->put("/penilaian/{$rina->id}", ['aksi' => 'simpan', 'scores' => $this->full()])
            ->assertForbidden();

        $this->get("/penilaian/{$rina->id}")->assertOk()->assertSee('Penilaian akhir dibuka mulai');
        $this->assertNull($rina->fresh()->assessment);
    }

    public function test_supervisor_menyimpan_draft_sebagian_dan_nilai_dihitung_per_kategori(): void
    {
        $rina = $this->open($this->internshipOf('mahasiswa@kp.test'));

        $this->actingAs($this->user('supervisor@kp.test'))
            ->put("/penilaian/{$rina->id}", ['aksi' => 'simpan', 'scores' => $this->scores(['hard' => [95, 85]])])
            ->assertRedirect("/penilaian/{$rina->id}");

        $assessment = $rina->fresh()->assessment;
        $this->assertSame('draft', $assessment->status);
        $this->assertCount(2, $assessment->scores);
        $this->assertEquals(31.5, (float) $assessment->final_score); // rata-rata hard 90 x 35%
        $this->assertNull($assessment->letter_grade);
        $this->assertNull($assessment->category_weights, 'Bobot baru disimpan saat dikirim');
    }

    public function test_penilaian_harus_lengkap_sebelum_dikirim(): void
    {
        $rina = $this->open($this->internshipOf('mahasiswa@kp.test'));

        $this->actingAs($this->user('supervisor@kp.test'))
            ->put("/penilaian/{$rina->id}", ['aksi' => 'kirim', 'scores' => $this->scores(['hard' => [95]])])
            ->assertSessionHasErrors('scores');

        $this->assertNull($rina->fresh()->assessment);
    }

    public function test_nilai_di_luar_skala_atau_pertanyaan_asing_ditolak(): void
    {
        $rina = $this->open($this->internshipOf('mahasiswa@kp.test'));
        $mentor = $this->user('supervisor@kp.test');
        $aspectId = AssessmentStandard::rubric()->aspects->first()->id;

        $this->actingAs($mentor)
            ->put("/penilaian/{$rina->id}", ['aksi' => 'simpan', 'scores' => [$aspectId => 90]])
            ->assertSessionHasErrors("scores.{$aspectId}");

        $this->actingAs($mentor)
            ->put("/penilaian/{$rina->id}", ['aksi' => 'simpan', 'scores' => [99999 => 85]])
            ->assertSessionHasErrors('scores');
    }

    public function test_setelah_dikirim_supervisor_tidak_bisa_mengubah_lagi(): void
    {
        $rina = $this->open($this->internshipOf('mahasiswa@kp.test'));
        $mentor = $this->user('supervisor@kp.test');

        $this->actingAs($mentor)
            ->put("/penilaian/{$rina->id}", ['aksi' => 'kirim', 'scores' => $this->full(85), 'comment' => 'Rajin dan teliti.'])
            ->assertRedirect('/penilaian');

        $assessment = $rina->fresh()->assessment;
        $this->assertSame('submitted', $assessment->status);
        $this->assertEquals(85, (float) $assessment->final_score);
        $this->assertEquals(['hard' => 35, 'soft' => 35, 'attitude' => 30], $assessment->category_weights);
        $this->assertSame('Rajin dan teliti.', $assessment->comment);

        $this->actingAs($mentor)
            ->put("/penilaian/{$rina->id}", ['aksi' => 'simpan', 'scores' => $this->full(45)])
            ->assertForbidden();

        $this->assertEquals(85, (float) $rina->fresh()->assessment->final_score);
    }

    public function test_supervisor_tidak_bisa_menilai_mahasiswa_bimbingan_supervisor_lain(): void
    {
        $dimas = $this->internshipOf('dimas@kp.test'); // bimbingan supervisor2

        $this->actingAs($this->user('supervisor@kp.test'))
            ->put("/penilaian/{$dimas->id}", ['aksi' => 'simpan', 'scores' => $this->full()])
            ->assertForbidden();

        $this->get("/penilaian/{$dimas->id}")->assertForbidden();
    }

    public function test_peran_lain_tidak_bisa_membuka_halaman_penilaian(): void
    {
        $this->actingAs($this->user('mahasiswa@kp.test'))->get('/penilaian')->assertForbidden();
        $this->actingAs($this->user('dospem@kp.test'))->get('/penilaian')->assertForbidden();
        $this->actingAs($this->user('supervisor@kp.test'))->get('/validasi')->assertForbidden();
        $this->actingAs($this->user('kaprodi@kp.test'))->get('/validasi')->assertForbidden();
        $this->actingAs($this->user('tu@kp.test'))->get('/penilaian')->assertForbidden();
    }

    public function test_dosen_pembimbing_memvalidasi_dan_huruf_mutu_terkunci_bersama_versi_skala(): void
    {
        $fajar = $this->internshipOf('fajar@kp.test'); // dikirim supervisor, menunggu validasi
        $lecturer = $this->user('dospem@kp.test');

        $this->actingAs($lecturer)->get('/validasi')->assertOk()->assertSee('Fajar Pratama')->assertSee('Menunggu validasi');
        $this->get("/validasi/{$fajar->id}")->assertOk()->assertSee('Rincian nilai')->assertSee('Hard skill')->assertSee('kontribusi');

        $this->patch("/validasi/{$fajar->id}", ['keputusan' => 'validasi', 'catatan' => 'Sesuai.'])
            ->assertRedirect('/validasi');

        $assessment = $fajar->fresh()->assessment;
        $this->assertSame('validated', $assessment->status);
        // hard (85, 95, 85, 75) = 85; soft (85, 85, 85, 95) = 87,5; attitude (85, 75, 85, 85, 85, 95) = 85
        $this->assertEquals(85.88, (float) $assessment->final_score); // 85 x 35% + 87,5 x 35% + 85 x 30%
        $this->assertSame('A', $assessment->letter_grade);
        $this->assertSame(Grading::currentVersion(), $assessment->grade_scale_version);
        $this->assertSame($lecturer->id, $assessment->validated_by);
        $this->assertTrue(ActivityLog::where('action', 'penilaian.validasi')->exists());

        // Tidak bisa diproses dua kali
        $this->patch("/validasi/{$fajar->id}", ['keputusan' => 'kembalikan', 'catatan' => 'Ulang'])->assertForbidden();
    }

    public function test_dosen_lain_tidak_bisa_memvalidasi(): void
    {
        $fajar = $this->internshipOf('fajar@kp.test');

        $this->actingAs($this->otherLecturer())
            ->patch("/validasi/{$fajar->id}", ['keputusan' => 'validasi'])
            ->assertForbidden();

        $this->assertSame('submitted', $fajar->fresh()->assessment->status);
    }

    public function test_draft_belum_bisa_divalidasi(): void
    {
        $dimas = $this->internshipOf('dimas@kp.test'); // masih draft

        $this->actingAs($this->user('dospem@kp.test'))
            ->patch("/validasi/{$dimas->id}", ['keputusan' => 'validasi'])
            ->assertForbidden();
    }

    public function test_dikembalikan_wajib_dengan_catatan_lalu_supervisor_bisa_memperbaiki(): void
    {
        $fajar = $this->internshipOf('fajar@kp.test');
        $lecturer = $this->user('dospem@kp.test');

        $this->actingAs($lecturer)
            ->patch("/validasi/{$fajar->id}", ['keputusan' => 'kembalikan'])
            ->assertSessionHasErrors('catatan');

        $this->actingAs($lecturer)
            ->patch("/validasi/{$fajar->id}", ['keputusan' => 'kembalikan', 'catatan' => 'Nilai komunikasi perlu ditinjau.'])
            ->assertRedirect('/validasi');

        $assessment = $fajar->fresh()->assessment;
        $this->assertSame('draft', $assessment->status);
        $this->assertTrue($assessment->wasReturned());

        $this->actingAs($this->user('supervisor@kp.test'))
            ->get("/penilaian/{$fajar->id}")
            ->assertOk()
            ->assertSee('Nilai komunikasi perlu ditinjau.');

        $this->put("/penilaian/{$fajar->id}", ['aksi' => 'kirim', 'scores' => $this->full(95)])
            ->assertRedirect('/penilaian');

        $this->assertSame('submitted', $fajar->fresh()->assessment->status);
        $this->assertEquals(95, (float) $fajar->fresh()->assessment->final_score);
    }

    public function test_mahasiswa_melihat_nilai_akhir_hanya_setelah_divalidasi(): void
    {
        $this->actingAs($this->user('salsa@kp.test'))->get('/dashboard')->assertOk()->assertSee('Sudah divalidasi');

        // Fajar: masih menunggu validasi, nilainya belum dibuka
        $this->actingAs($this->user('fajar@kp.test'))->get('/dashboard')->assertOk()
            ->assertDontSee('Sudah divalidasi')
            ->assertDontSee('Nilai akhir');
    }

    public function test_penilaian_tervalidasi_tetap_bisa_dilihat_tapi_tidak_bisa_diubah(): void
    {
        $salsa = $this->internshipOf('salsa@kp.test');

        $this->actingAs($this->user('supervisor2@kp.test'))
            ->get("/penilaian/{$salsa->id}")
            ->assertOk()
            ->assertSee('terkunci permanen')
            ->assertSee('Teliti dan bertanggung jawab atas akurasi data.')
            ->assertDontSee('Simpan draft');

        $this->put("/penilaian/{$salsa->id}", ['aksi' => 'simpan', 'scores' => $this->full(45)])->assertForbidden();
        $this->assertSame('A', Assessment::where('internship_id', $salsa->id)->value('letter_grade'));
    }

    // ---------- Kaprodi: bobot kategori ----------

    public function test_kaprodi_mengatur_bobot_dan_total_harus_100(): void
    {
        $kaprodi = $this->user('kaprodi@kp.test');

        $this->actingAs($kaprodi)->get('/kaprodi/bobot')->assertOk()->assertSee('Bobot kategori')->assertSee('Sangat baik');
        $this->put('/kaprodi/bobot', ['weights' => ['hard' => 50, 'soft' => 30, 'attitude' => 30]])->assertSessionHasErrors('weights');
        $this->put('/kaprodi/bobot', ['weights' => ['hard' => 40, 'soft' => 30, 'attitude' => 30]])->assertSessionHasNoErrors();

        $this->assertEquals(['hard' => 40, 'soft' => 30, 'attitude' => 30], AssessmentStandard::weights());
        $this->assertTrue(ActivityLog::where('action', 'penilaian.bobot')->exists());

        // Penilaian yang sudah dikirim tetap memakai bobot saat dikirim
        $fajar = $this->internshipOf('fajar@kp.test');
        $this->assertEquals(85.88, $fajar->assessment->computeFinalScore());

        foreach (['dospem@kp.test', 'admin@kp.test', 'supervisor@kp.test'] as $email) {
            $this->actingAs($this->user($email))->put('/kaprodi/bobot', ['weights' => ['hard' => 40, 'soft' => 30, 'attitude' => 30]])->assertForbidden();
        }
    }

    // ---------- Admin: standar penilaian ----------

    public function test_admin_mengatur_skala_nilai_dan_huruf_mutu(): void
    {
        $admin = $this->user('admin@kp.test');

        $this->actingAs($admin)->get('/admin/penilaian')->assertOk()->assertSee('Kualitas hasil kerja')->assertSee('Huruf mutu');

        $this->put('/admin/penilaian/skala', ['scale' => [['label' => 'Baik', 'value' => 90], ['label' => 'Cukup', 'value' => 90]]])
            ->assertSessionHasErrors();
        $this->put('/admin/penilaian/skala', ['scale' => [
            ['label' => 'Istimewa', 'value' => 100], ['label' => 'Baik', 'value' => 80], ['label' => 'Cukup', 'value' => 60], ['label' => '', 'value' => ''],
        ]])->assertSessionHasNoErrors();
        $this->assertSame([100.0, 80.0, 60.0], array_column(AssessmentStandard::scale(), 'value'));

        $this->put('/admin/penilaian/huruf', ['grades' => ['A' => 70, 'B+' => 75, 'B' => 60, 'C+' => 55, 'C' => 50, 'D' => 40, 'E' => 0]])
            ->assertSessionHasErrors('grades');
        $version = Grading::currentVersion();
        $this->put('/admin/penilaian/huruf', ['grades' => ['A' => 85, 'B+' => 78, 'B' => 70, 'C+' => 63, 'C' => 56, 'D' => 45, 'E' => 0]])
            ->assertSessionHasNoErrors();
        $this->assertSame($version + 1, Grading::currentVersion());
        $this->assertSame('B+', Grading::letterFor(80));

        $this->actingAs($this->user('kaprodi@kp.test'))->get('/admin/penilaian')->assertForbidden();
    }

    public function test_admin_mengubah_pertanyaan_yang_sudah_dipakai_membuat_versi_baru(): void
    {
        $admin = $this->user('admin@kp.test');
        $old = AssessmentStandard::rubric();
        $this->assertTrue($old->assessments()->exists(), 'Standar bawaan sudah dipakai penilaian contoh');

        $this->actingAs($admin)
            ->post('/admin/penilaian/pertanyaan', ['category' => 'hard', 'name' => 'Ketelitian kerja'])
            ->assertSessionHasNoErrors();

        $new = AssessmentStandard::rubric();
        $this->assertNotSame($old->id, $new->id);
        $this->assertSame(5, $new->aspects->where('category', 'hard')->count());
        $this->assertSame(4, $old->fresh()->aspects->where('category', 'hard')->count(), 'Penilaian lama tidak berubah');

        // Versi baru belum dipakai: ubah dan hapus langsung di versi itu
        $aspect = $new->aspects->firstWhere('name', 'Ketelitian kerja');
        $this->patch("/admin/penilaian/pertanyaan/{$aspect->id}", ['name' => 'Ketelitian dan kerapian'])->assertSessionHasNoErrors();
        $this->assertSame('Ketelitian dan kerapian', $aspect->fresh()->name);
        $this->delete("/admin/penilaian/pertanyaan/{$aspect->id}")->assertSessionHasNoErrors();
        $this->assertSame($new->id, AssessmentStandard::rubric()->id);
        $this->assertSame(4, AssessmentStandard::rubric()->aspects->where('category', 'hard')->count());

        // Penilaian baru memakai versi terbaru
        $rina = $this->open($this->internshipOf('mahasiswa@kp.test'));
        $this->actingAs($this->user('supervisor@kp.test'))->put("/penilaian/{$rina->id}", ['aksi' => 'simpan', 'scores' => $this->full(85)])->assertSessionHasNoErrors();
        $this->assertSame($new->id, $rina->fresh()->assessment->rubric_id);
    }
}

<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Assessment;
use App\Models\AssessmentRubric;
use App\Models\Internship;
use App\Models\User;
use App\Support\Grading;
use Database\Seeders\TestingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Penilaian akhir: Company Supervisor mengisi rubrik, dosen pembimbing memvalidasi, nilai terkunci. */
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

    /** @param  array<string, int|string>  $byName  nama aspek => nilai */
    private function scores(array $byName): array
    {
        $out = [];
        foreach (AssessmentRubric::active()->aspects as $aspect) {
            if (array_key_exists($aspect->name, $byName)) {
                $out[$aspect->id] = ['score' => $byName[$aspect->name], 'note' => null];
            }
        }

        return $out;
    }

    private function full(int $score = 90): array
    {
        return $this->scores(['Disiplin' => $score, 'Komunikasi' => $score, 'Kemampuan teknis' => $score, 'Kerja tim' => $score]);
    }

    public function test_supervisor_melihat_daftar_dan_form_penilaian_bimbingannya(): void
    {
        $rina = $this->internshipOf('mahasiswa@kp.test');

        $this->actingAs($this->user('supervisor@kp.test'))
            ->get('/penilaian')
            ->assertOk()
            ->assertSee('Rina Putri')
            ->assertSee('Belum dinilai');

        $this->get("/penilaian/{$rina->id}")
            ->assertOk()
            ->assertSee('Kemampuan teknis')
            ->assertSee('Kirim ke dosen pembimbing');
    }

    public function test_supervisor_menyimpan_draft_sebagian_dan_nilai_akhir_dihitung_dari_bobot(): void
    {
        $rina = $this->internshipOf('mahasiswa@kp.test');

        $this->actingAs($this->user('supervisor@kp.test'))
            ->put("/penilaian/{$rina->id}", ['aksi' => 'simpan', 'scores' => $this->scores(['Disiplin' => 80, 'Komunikasi' => 90])])
            ->assertRedirect("/penilaian/{$rina->id}");

        $assessment = $rina->fresh()->assessment;
        $this->assertSame('draft', $assessment->status);
        $this->assertCount(2, $assessment->scores);
        $this->assertEquals(42.5, (float) $assessment->final_score); // (80 + 90) x 25%
        $this->assertNull($assessment->letter_grade);                // huruf belum dikunci
    }

    public function test_penilaian_harus_lengkap_sebelum_dikirim(): void
    {
        $rina = $this->internshipOf('mahasiswa@kp.test');

        $this->actingAs($this->user('supervisor@kp.test'))
            ->put("/penilaian/{$rina->id}", ['aksi' => 'kirim', 'scores' => $this->scores(['Disiplin' => 80])])
            ->assertSessionHasErrors('scores');

        $this->assertNull($rina->fresh()->assessment);
    }

    public function test_nilai_di_luar_0_sampai_100_atau_aspek_asing_ditolak(): void
    {
        $rina = $this->internshipOf('mahasiswa@kp.test');
        $mentor = $this->user('supervisor@kp.test');
        $aspectId = AssessmentRubric::active()->aspects->first()->id;

        $this->actingAs($mentor)
            ->put("/penilaian/{$rina->id}", ['aksi' => 'simpan', 'scores' => [$aspectId => ['score' => 120]]])
            ->assertSessionHasErrors("scores.{$aspectId}.score");

        $this->actingAs($mentor)
            ->put("/penilaian/{$rina->id}", ['aksi' => 'simpan', 'scores' => [99999 => ['score' => 80]]])
            ->assertSessionHasErrors('scores');
    }

    public function test_setelah_dikirim_supervisor_tidak_bisa_mengubah_lagi(): void
    {
        $rina = $this->internshipOf('mahasiswa@kp.test');
        $mentor = $this->user('supervisor@kp.test');

        $this->actingAs($mentor)
            ->put("/penilaian/{$rina->id}", ['aksi' => 'kirim', 'scores' => $this->full(90)])
            ->assertRedirect('/penilaian');

        $this->assertSame('submitted', $rina->fresh()->assessment->status);

        $this->actingAs($mentor)
            ->put("/penilaian/{$rina->id}", ['aksi' => 'simpan', 'scores' => $this->full(50)])
            ->assertForbidden();

        $this->assertEquals(90, (float) $rina->fresh()->assessment->final_score);
    }

    public function test_supervisor_tidak_bisa_menilai_mahasiswa_bimbingan_mentor_lain(): void
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
        $this->actingAs($this->user('supervisor@kp.test'))->get('/validasi')->assertForbidden();
        $this->actingAs($this->user('kaprodi@kp.test'))->get('/validasi')->assertForbidden();
        $this->actingAs($this->user('tu@kp.test'))->get('/penilaian')->assertForbidden();
    }

    public function test_dosen_pembimbing_memvalidasi_dan_huruf_mutu_terkunci_bersama_versi_skala(): void
    {
        $fajar = $this->internshipOf('fajar@kp.test'); // dikirim supervisor, menunggu validasi
        $lecturer = $this->user('dospem@kp.test');

        $this->actingAs($lecturer)->get('/validasi')->assertOk()->assertSee('Fajar Pratama')->assertSee('Menunggu validasi');
        $this->get("/validasi/{$fajar->id}")->assertOk()->assertSee('Validasi dan kunci nilai');

        $this->patch("/validasi/{$fajar->id}", ['keputusan' => 'validasi', 'catatan' => 'Sesuai.'])
            ->assertRedirect('/validasi');

        $assessment = $fajar->fresh()->assessment;
        $this->assertSame('validated', $assessment->status);
        $this->assertEquals(82, (float) $assessment->final_score); // (84 + 78 + 86 + 80) / 4
        $this->assertSame('B+', $assessment->letter_grade);
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

        $this->put("/penilaian/{$fajar->id}", ['aksi' => 'kirim', 'scores' => $this->full(85)])
            ->assertRedirect('/penilaian');

        $this->assertSame('submitted', $fajar->fresh()->assessment->status);
    }

    public function test_mahasiswa_melihat_nilai_akhir_hanya_setelah_divalidasi(): void
    {
        // Salsa: sudah tervalidasi, huruf mutu tampil
        $this->actingAs($this->user('salsa@kp.test'))->get('/dashboard')->assertOk()->assertSee('Sudah divalidasi');

        // Fajar: masih menunggu validasi, nilainya belum dibuka (B+ adalah perkiraan nilainya)
        $this->actingAs($this->user('fajar@kp.test'))->get('/dashboard')->assertOk()
            ->assertDontSee('B+')
            ->assertDontSee('Nilai akhir');
    }

    public function test_dosen_pembimbing_menilai_seminar_bimbingannya(): void
    {
        $gilang = $this->internshipOf('gilang@kp.test');

        $this->actingAs($this->user('dospem@kp.test'))->get('/penilaian')->assertOk()->assertSee('Gilang Ramadhan')->assertDontSee('Rina Putri');
        $this->put("/penilaian/{$gilang->id}", ['aksi' => 'simpan', 'scores' => $this->scores(['Disiplin' => 80])])->assertRedirect("/penilaian/{$gilang->id}");

        // Supervisor perusahaan tidak menilai seminar; dospem tidak menilai magang (itu tugas Company Supervisor).
        $this->actingAs($this->user('supervisor@kp.test'))->put("/penilaian/{$gilang->id}", ['aksi' => 'simpan', 'scores' => $this->full()])->assertForbidden();
        $rina = $this->internshipOf('mahasiswa@kp.test');
        $this->actingAs($this->user('dospem@kp.test'))->put("/penilaian/{$rina->id}", ['aksi' => 'simpan', 'scores' => $this->full()])->assertForbidden();
    }

    public function test_penilaian_tervalidasi_tetap_bisa_dilihat_tapi_tidak_bisa_diubah(): void
    {
        $salsa = $this->internshipOf('salsa@kp.test');

        $this->actingAs($this->user('supervisor2@kp.test'))
            ->get("/penilaian/{$salsa->id}")
            ->assertOk()
            ->assertSee('terkunci permanen')
            ->assertDontSee('Kirim ke dosen pembimbing');

        $this->put("/penilaian/{$salsa->id}", ['aksi' => 'simpan', 'scores' => $this->full(10)])->assertForbidden();
        $this->assertSame('A', Assessment::where('internship_id', $salsa->id)->value('letter_grade'));
    }
}

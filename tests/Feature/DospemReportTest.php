<?php

namespace Tests\Feature;

use App\Actions\Reports\ManageReportChapters;
use App\Models\GuidanceSession;
use App\Models\Internship;
use App\Models\ReportComment;
use App\Models\User;
use Database\Seeders\TestingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Peran dosen pembimbing: diskusi dan berkas koreksi laporan, nilai laporan akhir, pengesahan sidang, jadwal bimbingan. */
class DospemReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TestingSeeder::class);
        Storage::fake('local');
    }

    private function user(string $email): User
    {
        return User::where('email', $email)->firstOrFail();
    }

    private function internshipOf(string $email): Internship
    {
        return Internship::where('student_id', $this->user($email)->id)->latest('id')->firstOrFail();
    }

    private function pdf(string $name = 'laporan.pdf'): UploadedFile
    {
        return UploadedFile::fake()->create($name, 120, 'application/pdf');
    }

    public function test_dosen_dan_mahasiswa_berdiskusi_per_bab_dengan_berkas_koreksi(): void
    {
        $rina = $this->internshipOf('mahasiswa@kp.test');
        $student = $rina->student;
        $lecturer = $rina->lecturer;

        // Menu Laporan KP mengarah ke laporan KP yang berjalan, lengkap dengan langkah dan draf otomatis
        $this->actingAs($student)->get('/laporan-kp')->assertRedirect("/laporan-kp/{$rina->id}");
        $this->actingAs($student)->get("/laporan-kp/{$rina->id}")->assertOk()->assertSee('Laporan akhir')->assertSee('Buka draf')->assertSee('Terbuka setelah semua bab disetujui');
        $chapter = $rina->fresh()->reportChapters()->where('needs_supervisor', false)->firstOrFail();
        $this->actingAs($student)->post("/dokumen/laporan/{$chapter->id}", ['berkas' => $this->pdf('bab1.pdf'), 'kembali' => 'laporan'])
            ->assertRedirect("/laporan-kp/{$rina->id}#bab-{$chapter->position}");

        // Dosen memberi komentar dan berkas koreksi
        $this->actingAs($lecturer)->post("/laporan-kp/{$rina->id}/komentar", ['bab' => $chapter->id, 'isi' => 'Perjelas tujuan penelitian.', 'berkas' => $this->pdf('koreksi-bab1.pdf')])
            ->assertRedirect("/laporan-kp/{$rina->id}#bab-{$chapter->position}");
        $comment = ReportComment::where('internship_id', $rina->id)->firstOrFail();
        $this->assertNotNull($comment->file_id);
        $this->assertSame(1, $student->notifications()->where('data', 'like', '%Berkas koreksi%')->count());

        // Mahasiswa membalas dan mengunduh berkas koreksi
        $this->actingAs($student)->post("/laporan-kp/{$rina->id}/komentar", ['bab' => $chapter->id, 'isi' => 'Baik, Pak.'])->assertRedirect();
        $this->assertSame(1, $lecturer->notifications()->where('data', 'like', '%membalas diskusi%')->count());
        $this->actingAs($student)->get("/laporan-kp/komentar/{$comment->id}/berkas")->assertOk();
        $this->actingAs($student)->get("/laporan-kp/{$rina->id}")->assertSee('Perjelas tujuan penelitian.')->assertSee('koreksi-bab1.pdf');

        // Komentar kosong ditolak; pihak lain tidak boleh melihat
        $this->actingAs($lecturer)->post("/laporan-kp/{$rina->id}/komentar", ['bab' => $chapter->id])->assertSessionHasErrors('isi');
        $this->actingAs($this->user('salsa@kp.test'))->get("/laporan-kp/{$rina->id}")->assertForbidden();
        $this->actingAs($this->user('salsa@kp.test'))->get("/laporan-kp/komentar/{$comment->id}/berkas")->assertForbidden();
        $this->actingAs($this->user('supervisor@kp.test'))->get("/laporan-kp/{$rina->id}")->assertForbidden();
    }

    public function test_dosen_menilai_laporan_akhir_dan_kaprodi_mengesahkan_nilai_sidang(): void
    {
        $salsa = $this->internshipOf('salsa@kp.test');
        $salsa->defenseScore()->delete();
        $lecturer = $salsa->lecturer;
        $this->assertTrue($salsa->assessment?->isValidated(), 'Data demo: penilaian Salsa sudah divalidasi');
        $industry = (float) $salsa->assessment->final_score;

        // Belum ada laporan akhir: nilai ditolak
        $points = ['writing' => 27, 'analysis' => 20, 'explaining' => 20, 'conduct' => 15];
        $this->actingAs($lecturer)->put("/laporan-kp/{$salsa->id}/nilai", $points)->assertSessionHasErrors('writing');

        // Laporan akhir terkunci sampai semua bab disetujui dosen
        ManageReportChapters::ensureFor($salsa);
        $salsa->reportChapters()->update(['status' => 'revisi']);
        $this->actingAs($salsa->student)->post("/laporan-kp/{$salsa->id}/akhir", ['berkas' => $this->pdf()])->assertSessionHasErrors('berkas');
        $salsa->reportChapters()->update(['status' => 'disetujui']);

        $this->actingAs($salsa->student)->post("/laporan-kp/{$salsa->id}/akhir", ['berkas' => $this->pdf('laporan-akhir.pdf')])->assertRedirect();
        $this->assertSame(1, $lecturer->notifications()->where('data', 'like', '%laporan akhir%')->count());

        // Poin melebihi bobot ditolak
        $this->actingAs($lecturer)->put("/laporan-kp/{$salsa->id}/nilai", ['writing' => 36] + $points)->assertSessionHasErrors('writing');
        $this->actingAs($lecturer)->put("/laporan-kp/{$salsa->id}/nilai", $points)->assertRedirect();
        $this->assertSame(82, (int) $salsa->fresh()->reportScore->total);
        $this->actingAs($salsa->student)->put("/laporan-kp/{$salsa->id}/nilai", $points)->assertForbidden();

        // Kaprodi mengesahkan: 50% dosen + 50% industri
        $kaprodi = $this->user('kaprodi@kp.test');
        $this->actingAs($kaprodi)->get('/kaprodi/sidang')->assertOk()->assertSee('Sahkan nilai sidang');
        $this->actingAs($kaprodi)->put("/kaprodi/sidang/{$salsa->id}", ['defended_at' => today()->toDateString()])->assertRedirect('/kaprodi/sidang');

        $defense = $salsa->fresh()->defenseScore;
        $this->assertEqualsWithDelta(round(82 * 0.5 + $industry * 0.5, 2), (float) $defense->score, 0.001);
        $this->assertSame('82.00', (string) $defense->lecturer_score);
        $this->assertNotNull($defense->letter_grade);
        $this->assertSame(1, $salsa->student->notifications()->where('data', 'like', '%Nilai sidang KP sudah disahkan%')->count());

        // Setelah disahkan, nilai laporan dan laporan akhir terkunci
        $this->actingAs($lecturer)->put("/laporan-kp/{$salsa->id}/nilai", ['writing' => 30] + $points)->assertSessionHasErrors('writing');
        $this->actingAs($salsa->student)->post("/laporan-kp/{$salsa->id}/akhir", ['berkas' => $this->pdf()])->assertSessionHasErrors('berkas');
        $this->actingAs($kaprodi)->get("/laporan-kp/{$salsa->id}/akhir/berkas")->assertOk();

        // Arsip hanya-baca: komentar dan unggah bab ditolak, tampil keterangan arsip
        $this->actingAs($salsa->student)->get("/laporan-kp/{$salsa->id}")->assertSee('Nilai sidang sudah disahkan')->assertDontSee('Tulis pertanyaan atau tanggapan');
        $this->actingAs($lecturer)->post("/laporan-kp/{$salsa->id}/komentar", ['isi' => 'Tambahan'])->assertSessionHasErrors('isi');
        $bab = $salsa->reportChapters()->first();
        $bab->update(['status' => 'revisi']);
        $this->actingAs($salsa->student)->post("/dokumen/laporan/{$bab->id}", ['berkas' => $this->pdf()])->assertSessionHasErrors('berkas');
    }

    public function test_pengesahan_ditolak_bila_nilai_dosen_belum_ada(): void
    {
        $salsa = $this->internshipOf('salsa@kp.test');
        $salsa->defenseScore()->delete();

        $this->actingAs($this->user('kaprodi@kp.test'))->put("/kaprodi/sidang/{$salsa->id}", ['defended_at' => today()->toDateString()])
            ->assertSessionHasErrors('defended_at');
        $this->assertNull($salsa->fresh()->defenseScore);
    }

    public function test_dosen_menjadwalkan_bimbingan_dengan_tautan_rapat(): void
    {
        $rina = $this->internshipOf('mahasiswa@kp.test');
        $lecturer = $rina->lecturer;
        $date = today()->addDays(2)->toDateString();

        // Daring wajib tautan https
        $this->actingAs($lecturer)->post('/antrean-bimbingan/jadwal', ['internship_id' => $rina->id, 'held_on' => $date, 'start_time' => '10:00', 'mode' => 'daring', 'topic' => 'Revisi bab 3'])
            ->assertSessionHasErrorsIn('jadwal', 'meeting_link');

        $this->actingAs($lecturer)->post('/antrean-bimbingan/jadwal', ['internship_id' => $rina->id, 'held_on' => $date, 'start_time' => '10:00', 'mode' => 'daring', 'topic' => 'Revisi bab 3', 'meeting_link' => 'https://meet.google.com/abc-defg-hij'])
            ->assertSessionHasNoErrors();
        $session = GuidanceSession::where('internship_id', $rina->id)->where('status', 'scheduled')->firstOrFail();
        $this->assertSame(1, $rina->student->notifications()->where('data', 'like', '%Jadwal bimbingan%')->count());

        // Mahasiswa melihat tombol Gabung dan tidak bisa menghapus jadwal dosen
        $this->actingAs($rina->student)->get('/bimbingan')->assertSee('Gabung')->assertSee('https://meet.google.com/abc-defg-hij');
        $this->actingAs($rina->student)->delete("/bimbingan/{$session->id}")->assertForbidden();

        // Belum berlangsung: belum bisa ditandai selesai; setelah berlangsung terhitung terkonfirmasi
        $this->actingAs($lecturer)->patch("/antrean-bimbingan/{$session->id}", ['keputusan' => 'konfirmasi'])->assertSessionHasErrors('keputusan');
        $session->update(['held_on' => today()]);
        $before = $rina->confirmedGuidanceCount();
        $this->actingAs($lecturer)->patch("/antrean-bimbingan/{$session->id}", ['keputusan' => 'konfirmasi'])->assertSessionHasNoErrors();
        $this->assertSame($before + 1, $rina->confirmedGuidanceCount());

        // Dosen lain tidak bisa menjadwalkan untuk mahasiswa ini
        $other = User::where('role', 'dospem')->where('id', '!=', $lecturer->id)->first();
        if ($other) {
            $this->actingAs($other)->post('/antrean-bimbingan/jadwal', ['internship_id' => $rina->id, 'held_on' => $date, 'start_time' => '09:00', 'mode' => 'luring', 'topic' => 'X'])->assertNotFound();
        }
    }
}

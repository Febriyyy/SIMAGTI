<?php

namespace Tests\Feature;

use App\Models\AssessmentRubric;
use App\Models\Internship;
use App\Models\User;
use Database\Seeders\TestingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Semua halaman tahap 2 terbuka untuk peran yang berhak, dan tertutup untuk peran lain. */
class StageTwoPagesTest extends TestCase
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

    private function internshipOf(string $email): Internship
    {
        return Internship::where('student_id', $this->user($email)->id)->latest('id')->firstOrFail();
    }

    public function test_halaman_mahasiswa_terbuka(): void
    {
        $rina = $this->user('mahasiswa@kp.test');
        foreach (['/bulanan' => 'Evaluasi Bulanan', '/bimbingan' => 'Riwayat bimbingan', '/dokumen' => 'Surat pengantar', '/perubahan' => 'Ajukan perubahan', '/notifikasi' => 'Kabar terbaru', '/status-kp' => 'Bimbingan dosen pembimbing'] as $url => $text) {
            $this->actingAs($rina)->get($url)->assertOk()->assertSee($text);
        }

        // Tari (KP mandiri MBKM): surat balasan menunggu diperiksa, pakta integritas belum
        $this->actingAs($this->user('tari@kp.test'))->get('/dokumen')->assertOk()
            ->assertSee('Surat balasan perusahaan')->assertSee('Menunggu diperiksa')->assertSee('Pakta integritas MBKM');

        // Dimas (MBKM): formulir rekognisi SKS di Status KP
        $this->actingAs($this->user('dimas@kp.test'))->get('/status-kp')->assertOk()->assertSee('Rekognisi SKS')->assertSee('Analisis Data Lanjut');
    }

    public function test_halaman_staf_terbuka_sesuai_peran(): void
    {
        $pages = [
            'supervisor@kp.test' => ['/antrean-perubahan' => 'Perpanjangan periode', '/dashboard' => 'Mahasiswa magang'],
            'supervisor2@kp.test' => ['/dashboard' => 'Konfirmasi penempatan'],
            'dospem@kp.test' => ['/antrean-bimbingan' => 'Menunggu konfirmasi', '/hasil' => 'Hasil KP', '/kuesioner' => 'Kuesioner KP Industri 2026'],
            'kaprodi@kp.test' => ['/antrean-perubahan' => 'Pengajuan Perubahan', '/kaprodi/rekognisi' => 'Dimas Aditya'],
            'tu@kp.test' => ['/tu/surat' => 'Nadia Kirana', '/antrean-perubahan' => 'Ganti Company Supervisor', '/tu/aktivasi' => 'Menunggu aktivasi'],
            'biro@kp.test' => ['/biro/dokumen' => 'Tari Anjani'],
        ];
        foreach ($pages as $email => $urls) {
            foreach ($urls as $url => $text) {
                $this->actingAs($this->user($email))->get($url)->assertOk()->assertSee($text);
            }
        }

        $rina = $this->internshipOf('mahasiswa@kp.test');
        $this->actingAs($this->user('supervisor@kp.test'))->get("/pantau/{$rina->id}?tab=bulanan")->assertOk()->assertSee('Simpan nilai');
        $this->actingAs($this->user('dospem@kp.test'))->get("/pantau/{$rina->id}?tab=bulanan")->assertOk()->assertSee('Catatan dosen pembimbing');
        $this->actingAs($this->user('dospem@kp.test'))->get("/pantau/{$rina->id}?tab=bimbingan")->assertOk()->assertSee('Konfirmasi');
        $this->actingAs($this->user('dospem@kp.test'))->get("/hasil/{$rina->id}")->assertOk()->assertSee('Bimbingan terkonfirmasi 3 dari minimal 5');

        $rubric = AssessmentRubric::where('owner_id', $this->user('dospem@kp.test')->id)->firstOrFail();
        $this->actingAs($this->user('dospem@kp.test'))->get("/kuesioner/{$rubric->id}")->assertOk()->assertSee('Saran pengembangan');

        // Bel notifikasi tampil di topbar
        $this->actingAs($this->user('mahasiswa@kp.test'))->get('/dashboard')->assertOk()->assertSee('bell-count', false);
    }

    public function test_peran_lain_tidak_bisa_membuka(): void
    {
        $this->actingAs($this->user('mahasiswa@kp.test'))->get('/hasil')->assertForbidden();
        $this->actingAs($this->user('mahasiswa@kp.test'))->get('/tu/surat')->assertForbidden();
        $this->actingAs($this->user('supervisor@kp.test'))->get('/bulanan')->assertForbidden();
        $this->actingAs($this->user('dospem@kp.test'))->get('/biro/dokumen')->assertForbidden();
        $this->actingAs($this->user('biro@kp.test'))->get('/kaprodi/rekognisi')->assertForbidden();

        // Dosen lain / supervisor lain tidak boleh menilai bulanan mahasiswa yang bukan bimbingannya
        $dimas = $this->internshipOf('dimas@kp.test');
        $this->actingAs($this->user('supervisor@kp.test'))
            ->patch("/pantau/{$dimas->id}/bulanan/1", ['score_hard' => 3, 'score_soft' => 3, 'score_attitude' => 3])->assertForbidden();

        // Berkas dokumen hanya untuk pihak terkait
        $tari = $this->internshipOf('tari@kp.test');
        $doc = $tari->documents()->where('type', 'surat_balasan')->firstOrFail();
        $this->actingAs($this->user('mahasiswa@kp.test'))->get("/dokumen/{$doc->id}/berkas")->assertForbidden();
        $this->actingAs($this->user('biro@kp.test'))->get("/dokumen/{$doc->id}/berkas")->assertOk();
    }

    public function test_nama_ringkas_untuk_daftar(): void
    {
        $this->assertSame('Dewi Lestari W.', (new User(['name' => 'Dewi Lestari Wulandari, S.Kom., M.Kom.']))->listName());
        $this->assertSame('Hendra Kusuma', (new User(['name' => 'Dr. Hendra Kusuma, M.Kom']))->listName());
        $this->assertSame('Rina Putri', (new User(['name' => 'Rina Putri']))->listName());
    }
}

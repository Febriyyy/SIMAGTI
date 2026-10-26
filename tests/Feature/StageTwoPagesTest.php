<?php

namespace Tests\Feature;

use App\Models\Internship;
use App\Models\User;
use Database\Seeders\TestingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Halaman tiap peran terbuka untuk peran yang berhak, dan tertutup untuk peran lain. */
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
        foreach (['/timesheet' => 'Rencana kegiatan', '/kegiatan' => 'Goals', '/bimbingan' => 'Riwayat bimbingan', '/dokumen' => 'Surat pengantar',
            '/perubahan' => 'Ajukan perubahan', '/notifikasi' => 'Kabar terbaru', '/status-kp' => 'Bimbingan dosen pembimbing'] as $url => $text) {
            $this->actingAs($rina)->get($url)->assertOk()->assertSee($text);
        }

        // Tari (KP mandiri): surat balasan menunggu diperiksa, tanpa pakta integritas
        $this->actingAs($this->user('tari@kp.test'))->get('/dokumen')->assertOk()
            ->assertSee('Surat balasan perusahaan')->assertSee('Menunggu diperiksa')->assertDontSee('Pakta integritas');

        // Halaman yang sudah dihapus
        foreach (['/silabus', '/bulanan', '/rekognisi', '/kuesioner'] as $url) {
            $this->actingAs($rina)->get($url)->assertNotFound();
        }
    }

    public function test_halaman_staf_terbuka_sesuai_peran(): void
    {
        $pages = [
            'supervisor@kp.test' => ['/antrean-perubahan' => 'Perpanjangan periode', '/dashboard' => 'Mahasiswa magang', '/aktivitas' => 'Linimasa'],
            'supervisor2@kp.test' => ['/dashboard' => 'Konfirmasi penempatan'],
            'dospem@kp.test' => ['/antrean-bimbingan' => 'Menunggu konfirmasi', '/hasil' => 'Hasil KP', '/aktivitas' => 'Linimasa'],
            'kaprodi@kp.test' => ['/antrean-perubahan' => 'Pengajuan Perubahan', '/kaprodi/bobot' => 'Bobot kategori', '/aktivitas' => 'Linimasa'],
            'tu@kp.test' => ['/tu/surat' => 'Nadia Kirana', '/antrean-perubahan' => 'Ganti Company Supervisor', '/tu/aktivasi' => 'Menunggu aktivasi',
                '/tu/dokumen' => 'Tari Anjani', '/tu/verifikasi' => 'Nadia Kirana', '/perusahaan' => 'PT Nusantara Digital'],
            'admin@kp.test' => ['/admin/akun' => 'Rina Putri', '/admin/penilaian' => 'Skala nilai', '/admin/log-aktivitas' => 'Log Aktivitas', '/dashboard' => 'Akun per peran'],
        ];
        foreach ($pages as $email => $urls) {
            foreach ($urls as $url => $text) {
                $this->actingAs($this->user($email))->get($url)->assertOk()->assertSee($text);
            }
        }

        $rina = $this->internshipOf('mahasiswa@kp.test');
        $this->actingAs($this->user('supervisor@kp.test'))->get("/pantau/{$rina->id}?tab=rencana")->assertOk()->assertSee('Rencana kegiatan')->assertSee('Goals');
        $this->actingAs($this->user('dospem@kp.test'))->get("/pantau/{$rina->id}?tab=aktivitas")->assertOk()->assertSee('Aktivitas');
        $this->actingAs($this->user('dospem@kp.test'))->get("/pantau/{$rina->id}?tab=bimbingan")->assertOk()->assertSee('Konfirmasi');
        $this->actingAs($this->user('dospem@kp.test'))->get("/hasil/{$rina->id}")->assertOk()->assertSee('Bimbingan terkonfirmasi 3 dari minimal 5');

        // Bel notifikasi tampil di topbar
        $this->actingAs($this->user('mahasiswa@kp.test'))->get('/dashboard')->assertOk()->assertSee('bell-count', false);
    }

    public function test_peran_lain_tidak_bisa_membuka(): void
    {
        $this->actingAs($this->user('mahasiswa@kp.test'))->get('/hasil')->assertForbidden();
        $this->actingAs($this->user('mahasiswa@kp.test'))->get('/tu/surat')->assertForbidden();
        $this->actingAs($this->user('mahasiswa@kp.test'))->get('/aktivitas')->assertForbidden();
        $this->actingAs($this->user('dospem@kp.test'))->get('/tu/dokumen')->assertForbidden();
        $this->actingAs($this->user('tu@kp.test'))->get('/kaprodi/bobot')->assertForbidden();
        $this->actingAs($this->user('kaprodi@kp.test'))->get('/admin/akun')->assertForbidden();
        $this->actingAs($this->user('admin@kp.test'))->get('/pantau')->assertForbidden();

        // Berkas dokumen hanya untuk pihak terkait
        $tari = $this->internshipOf('tari@kp.test');
        $doc = $tari->documents()->where('type', 'surat_balasan')->firstOrFail();
        $this->actingAs($this->user('mahasiswa@kp.test'))->get("/dokumen/{$doc->id}/berkas")->assertForbidden();
        $this->actingAs($this->user('tu@kp.test'))->get("/dokumen/{$doc->id}/berkas")->assertOk();
    }

    public function test_nama_ringkas_untuk_daftar(): void
    {
        $this->assertSame('Dewi Lestari W.', (new User(['name' => 'Dewi Lestari Wulandari, S.Kom., M.Kom.']))->listName());
        $this->assertSame('Hendra Kusuma', (new User(['name' => 'Dr. Hendra Kusuma, M.Kom']))->listName());
        $this->assertSame('Rina Putri', (new User(['name' => 'Rina Putri']))->listName());
    }
}

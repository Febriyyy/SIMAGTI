<?php

namespace Tests\Feature;

use App\Models\Internship;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Data bawaan aplikasi (realistis, fiktif) bisa dimuat dan dipakai login untuk setiap peran. */
class DataSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_data_bawaan_termuat_dan_setiap_peran_bisa_masuk(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(29, User::count());
        $this->assertSame(0, User::where('email', 'like', '%@kp.test')->count()); // tidak ada akun contoh
        $this->assertSame(0, User::whereIn('role', ['mentor', 'ketua_divisi'])->count());
        $this->assertSame(1, Internship::awaitingKaprodi()->count());      // Ilham
        $this->assertSame(2, Internship::awaitingSupervisor()->count());   // Farhan dan Tiara, satu supervisor
        $this->assertSame(2, Internship::where('program', 'seminar')->count());

        foreach (['arief.hakim@kampus.ac.id', 'bambang.sutrisno@kampus.ac.id', 'wulan.anggraini@kampus.ac.id', 'retno.kusumaningrum@kampus.ac.id',
                  'andika.saputra@cakrawaladigital.co.id', 'nabila.azzahra@mhs.kampus.ac.id', 'yudha.pradana@mhs.kampus.ac.id',
                  'citra.kirana@mhs.kampus.ac.id'] as $email) {
            $this->post('/login', ['email' => $email, 'password' => DataSeeder::PASSWORD])->assertRedirect('/dashboard');
            $this->get('/dashboard')->assertOk();
            $this->post('/logout');
        }

        // Halaman utama tiap peran terbuka tanpa galat dengan data bawaan
        $pages = [
            'nabila.azzahra@mhs.kampus.ac.id' => ['/status-kp', '/silabus', '/kegiatan', '/kegiatan?tampilan=list', '/timesheet'],
            'andika.saputra@cakrawaladigital.co.id' => ['/pantau', '/penilaian'],
            'retno.kusumaningrum@kampus.ac.id' => ['/tu/aktivasi', '/penempatan'],
            'arief.hakim@kampus.ac.id' => ['/pantau', '/kaprodi/persetujuan', '/penempatan'],
        ];
        foreach ($pages as $email => $urls) {
            $this->actingAs(User::where('email', $email)->firstOrFail());
            foreach ($urls as $url) {
                $this->get($url)->assertOk();
            }
        }
    }

    public function test_halaman_login_tidak_menampilkan_akun_contoh(): void
    {
        $this->get('/login')->assertOk()->assertDontSee('Akun contoh')->assertDontSee('@kp.test')->assertDontSee(DataSeeder::PASSWORD);
    }
}

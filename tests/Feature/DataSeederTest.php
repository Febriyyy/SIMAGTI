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

        $this->assertSame(28, User::count());
        $this->assertSame(0, User::where('email', 'like', '%@kp.test')->count()); // tidak ada akun contoh
        $this->assertSame(1, Internship::awaitingKaprodi()->count());

        foreach (['arief.hakim@kampus.ac.id', 'bambang.sutrisno@kampus.ac.id', 'wulan.anggraini@kampus.ac.id',
                  'hendro.wibisono@cakrawaladigital.co.id', 'andika.saputra@cakrawaladigital.co.id', 'nabila.azzahra@mhs.kampus.ac.id'] as $email) {
            $this->post('/login', ['email' => $email, 'password' => DataSeeder::PASSWORD])->assertRedirect('/dashboard');
            $this->get('/dashboard')->assertOk();
            $this->post('/logout');
        }
    }

    public function test_halaman_login_tidak_menampilkan_akun_contoh(): void
    {
        $this->get('/login')->assertOk()->assertDontSee('Akun contoh')->assertDontSee('@kp.test')->assertDontSee(DataSeeder::PASSWORD);
    }
}

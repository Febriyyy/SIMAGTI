<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\TestingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ErrorPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TestingSeeder::class);
    }

    public function test_halaman_tidak_ditemukan_memakai_tampilan_simagti(): void
    {
        $this->get('/halaman-yang-tidak-ada')
            ->assertNotFound()
            ->assertSee('Halaman tidak ditemukan')
            ->assertSee('class="err-card"', false)
            ->assertSee('Ke dashboard');
    }

    public function test_halaman_tanpa_akses_memakai_tampilan_simagti(): void
    {
        $this->actingAs(User::where('email', 'mahasiswa@kp.test')->firstOrFail())
            ->get('/admin/akun')
            ->assertForbidden()
            ->assertSee('Tidak punya akses')
            ->assertSee('class="err-card"', false);
    }
}

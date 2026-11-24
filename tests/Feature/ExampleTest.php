<?php

namespace Tests\Feature;

use App\Models\JobPosting;
use App\Models\User;
use Database\Seeders\TestingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Halaman depan: tamu melihat landing page, pengguna yang sudah masuk langsung ke dasbor. */
class ExampleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TestingSeeder::class);
    }

    public function test_tamu_melihat_landing_page(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('Magang terpantau')
            ->assertSee('Satu sistem untuk setiap peran.')
            ->assertSee('Empat tahap, semuanya tercatat.')
            ->assertSee(route('login'));
    }

    public function test_pengguna_yang_sudah_masuk_diarahkan_ke_dasbor(): void
    {
        $this->actingAs(User::where('email', 'admin@kp.test')->firstOrFail())
            ->get('/')->assertRedirect(route('dashboard'));
    }

    public function test_hanya_lowongan_yang_dibuka_tampil(): void
    {
        $open = JobPosting::where('status', 'published')->first();
        $draft = JobPosting::where('status', '!=', 'published')->first();

        $response = $this->get('/')->assertOk();

        if ($open) {
            $response->assertSee('Lowongan dari perusahaan mitra.')->assertSee($open->title);
        } else {
            $response->assertDontSee('Lowongan dari perusahaan mitra.');
        }
        if ($draft) {
            $response->assertDontSee($draft->title);
        }
    }
}

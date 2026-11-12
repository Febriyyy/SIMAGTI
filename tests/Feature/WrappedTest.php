<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\TestingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WrappedTest extends TestCase
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

    public function test_kp_wrapped_terbuka_setelah_kp_selesai(): void
    {
        $salsa = $this->user('salsa@kp.test');

        $this->actingAs($salsa)->get('/dashboard')->assertOk()
            ->assertSee('KP Wrapped kamu sudah siap')->assertSee(route('wrapped'));

        $this->get('/wrapped')->assertOk()
            ->assertSee('KP Wrapped')
            ->assertSee('Hai, '.$salsa->firstName())
            ->assertSee('hari kamu mencatat kegiatan')
            ->assertSee('task selesai')
            ->assertSee('Simpan gambar')
            ->assertSee('data-wr-data', false);
    }

    public function test_kp_wrapped_belum_tersedia_selama_kp_berjalan(): void
    {
        $this->actingAs($this->user('mahasiswa@kp.test'))->get('/dashboard')->assertOk()->assertDontSee('KP Wrapped kamu sudah siap');
        $this->get('/wrapped')->assertNotFound();
        $this->actingAs($this->user('dospem@kp.test'))->get('/wrapped')->assertForbidden();
    }
}

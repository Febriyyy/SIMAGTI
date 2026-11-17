<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\TestingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Rekap KP tingkat prodi untuk Kaprodi dan TU Fakultas, beserta unduhan Excel. */
class RecapTest extends TestCase
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

    public function test_kaprodi_dan_tu_melihat_rekap_dan_mengunduh_excel(): void
    {
        foreach (['kaprodi@kp.test', 'tu@kp.test'] as $email) {
            $this->actingAs($this->user($email))->get('/rekap')->assertOk()
                ->assertSee('Rekap KP')->assertSee('Salsa')->assertSee('Unduh Excel');

            $response = $this->actingAs($this->user($email))->get('/rekap/unduh?status=selesai');
            $response->assertOk();
            $this->assertStringContainsString('spreadsheetml', (string) $response->headers->get('Content-Type'));
        }
    }

    public function test_filter_status_dan_pencarian(): void
    {
        $kaprodi = $this->user('kaprodi@kp.test');

        $this->actingAs($kaprodi)->get('/rekap?status=selesai')->assertOk()->assertSee('Salsa')->assertDontSee('Fajar');
        $this->actingAs($kaprodi)->get('/rekap?status=semua&q=Fajar')->assertOk()->assertSee('Fajar')->assertDontSee('Salsa Nabila');
    }

    public function test_peran_lain_tidak_bisa_membuka_rekap(): void
    {
        foreach (['dospem@kp.test', 'supervisor@kp.test', 'mahasiswa@kp.test', 'admin@kp.test'] as $email) {
            $this->actingAs($this->user($email))->get('/rekap')->assertForbidden();
        }
    }
}

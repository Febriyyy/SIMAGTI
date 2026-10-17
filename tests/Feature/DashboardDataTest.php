<?php

namespace Tests\Feature;

use App\Actions\Dashboard\BuildMonitoringDashboard;
use App\Models\Company;
use App\Models\Internship;
use App\Models\User;
use Database\Seeders\TestingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** Dashboard dospem, kaprodi, ketua divisi, biro, dan statistik perusahaan memakai data nyata. */
class DashboardDataTest extends TestCase
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

    private function build(string $email): array
    {
        return app(BuildMonitoringDashboard::class)->handle($this->user($email));
    }

    public function test_setiap_peran_pemantau_hanya_menghitung_penempatan_yang_boleh_dilihat(): void
    {
        $names = fn (array $data) => $data['internships']->map(fn ($i) => $i->student->name)->sort()->values()->all();

        // Ketua divisi Data Prima: Dimas (berjalan) dan Salsa (selesai)
        $this->assertSame(['Dimas Aditya', 'Salsa Nabila'], $names($this->build('divisi2@kp.test')));

        // Ketua divisi Nusantara: Fajar dan Rina
        $this->assertSame(['Fajar Pratama', 'Rina Putri'], $names($this->build('divisi@kp.test')));

        // Kaprodi melihat semuanya, termasuk KP mandiri yang masih diajukan
        $this->assertCount(5, $this->build('kaprodi@kp.test')['internships']);
    }

    public function test_mahasiswa_tanpa_bukti_baru_masuk_daftar_perlu_perhatian_beserta_alasannya(): void
    {
        $attention = $this->build('dospem@kp.test')['attention'];
        $dimas = $attention->first(fn ($row) => $row['internship']->student->name === 'Dimas Aditya');

        $this->assertNotNull($dimas);
        $this->assertStringContainsString('Tidak ada bukti baru', implode(' ', $dimas['reasons']));

        // Rina aktif mengirim bukti minggu ini, jadi tidak ditandai karena itu
        $rina = $attention->first(fn ($row) => $row['internship']->student->name === 'Rina Putri');
        $this->assertTrue($rina === null || ! str_contains(implode(' ', $rina['reasons']), 'bukti'));

        $this->actingAs($this->user('dospem@kp.test'))
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Perlu perhatian')
            ->assertSee('Tidak ada bukti baru');
    }

    public function test_ringkasan_penilaian_dan_sebaran_huruf_mutu(): void
    {
        $data = $this->build('kaprodi@kp.test');

        $this->assertSame(1, $data['assessment']['validated']);   // Salsa
        $this->assertSame(1, $data['assessment']['submitted']);   // Fajar
        $this->assertSame(1, $data['assessment']['draft']);       // Dimas
        $this->assertSame(1, $data['assessment']['none']);        // Rina
        $this->assertSame(1, $data['grades']['A']);
        $this->assertSame(88.75, $data['stats']['average_score']);
        $this->assertSame(array_key_first($data['grades']), 'A'); // urut dari huruf tertinggi
    }

    public function test_kaprodi_melihat_ringkasan_per_perusahaan_dan_antrean_persetujuan(): void
    {
        $data = $this->build('kaprodi@kp.test');

        $this->assertSame(0, $data['waitingApproval']); // Nadia belum diverifikasi biro
        $this->assertSame(Internship::awaitingKaprodi()->count(), $data['waitingApproval']);

        $companies = $data['companies']->keyBy(fn ($row) => $row['company']->name);
        $this->assertSame(2, $companies['PT Nusantara Digital']['active']);
        $this->assertSame(88.75, $companies['PT Data Prima']['score']);

        $this->actingAs($this->user('kaprodi@kp.test'))->get('/dashboard')->assertOk()->assertSee('Per perusahaan');
    }

    public function test_ketua_divisi_melihat_penilaian_yang_menunggu_validasinya(): void
    {
        $this->actingAs($this->user('divisi@kp.test'))
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Menunggu validasi')
            ->assertSee('Fajar Pratama');

        $this->assertCount(0, $this->build('divisi2@kp.test')['waitingValidation']);
    }

    public function test_halaman_statistik_perusahaan_untuk_biro_dan_kaprodi(): void
    {
        $dataPrima = Company::where('name', 'PT Data Prima')->firstOrFail();

        $this->actingAs($this->user('biro@kp.test'))
            ->get("/perusahaan/{$dataPrima->id}")
            ->assertOk()
            ->assertSee('Sebaran skill')
            ->assertSee('Performa per aspek')
            ->assertSee('Salsa Nabila');

        $this->actingAs($this->user('kaprodi@kp.test'))->get("/perusahaan/{$dataPrima->id}")->assertOk();
        $this->actingAs($this->user('mahasiswa@kp.test'))->get("/perusahaan/{$dataPrima->id}")->assertForbidden();
        $this->actingAs($this->user('dospem@kp.test'))->get("/perusahaan/{$dataPrima->id}")->assertForbidden();
    }

    public function test_statistik_perusahaan_memuat_durasi_dan_performa_dari_nilai_tervalidasi(): void
    {
        $dataPrima = Company::where('name', 'PT Data Prima')->firstOrFail();
        Sanctum::actingAs($this->user('biro@kp.test'));

        $data = $this->getJson("/api/v1/companies/{$dataPrima->id}/stats")->assertOk()->json('data');

        $this->assertSame(1, $data['performance']['assessed_count']);
        $this->assertEquals(88.75, $data['performance']['average_final_score']);
        $this->assertSame(1, $data['performance']['grades']['A']);
        $this->assertCount(4, $data['performance']['aspects']);
        $this->assertGreaterThan(0, $data['average_duration_months']);

        // Nilai mahasiswa hanya dibuka jika sudah divalidasi (Dimas masih draft)
        $students = collect($data['students'])->keyBy('name');
        $this->assertSame('A', $students['Salsa Nabila']['letter_grade']);
        $this->assertNull($students['Dimas Aditya']['final_score']);
    }

    public function test_dashboard_biro_menampilkan_tautan_statistik(): void
    {
        $this->actingAs($this->user('biro@kp.test'))
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Statistik')
            ->assertSee('PT Data Prima');
    }
}

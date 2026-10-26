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

/** Dashboard setiap peran (mahasiswa, Company Supervisor, dospem, kaprodi, TU, admin) memakai data nyata. */
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

        // Company Supervisor Data Prima: Dimas (berjalan) dan Salsa (selesai); Tari belum punya supervisor
        $this->assertSame(['Dimas Aditya', 'Salsa Nabila'], $names($this->build('supervisor2@kp.test')));

        // Company Supervisor Nusantara: Fajar, Gilang, dan Rina
        $this->assertSame(['Fajar Pratama', 'Gilang Ramadhan', 'Rina Putri'], $names($this->build('supervisor@kp.test')));

        // Dosen pembimbing: semua bimbingannya
        $this->assertSame(['Dimas Aditya', 'Fajar Pratama', 'Gilang Ramadhan', 'Rina Putri', 'Salsa Nabila', 'Tari Anjani'], $names($this->build('dospem@kp.test')));

        // Kaprodi melihat semuanya, termasuk KP mandiri yang masih diajukan
        $this->assertCount(7, $this->build('kaprodi@kp.test')['internships']);
    }

    public function test_mahasiswa_tanpa_kegiatan_baru_masuk_daftar_perlu_perhatian_beserta_alasannya(): void
    {
        $attention = $this->build('dospem@kp.test')['attention'];
        $dimas = $attention->first(fn ($row) => $row['internship']->student->name === 'Dimas Aditya');

        $this->assertNotNull($dimas);
        $this->assertStringContainsString('Tidak ada kegiatan baru', implode(' ', $dimas['reasons']));

        // Rina mencatat kegiatan setiap hari, jadi tidak ditandai karena itu
        $rina = $attention->first(fn ($row) => $row['internship']->student->name === 'Rina Putri');
        $this->assertTrue($rina === null || ! str_contains(implode(' ', $rina['reasons']), 'kegiatan'));

        $this->actingAs($this->user('dospem@kp.test'))
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Perlu perhatian')
            ->assertSee('Tidak ada kegiatan baru');
    }

    public function test_ringkasan_penilaian_dan_sebaran_huruf_mutu(): void
    {
        $data = $this->build('kaprodi@kp.test');

        $this->assertSame(1, $data['assessment']['validated']);   // Salsa
        $this->assertSame(1, $data['assessment']['submitted']);   // Fajar
        $this->assertSame(1, $data['assessment']['draft']);       // Dimas
        $this->assertSame(3, $data['assessment']['none']);        // Rina, Tari, Gilang
        $this->assertSame(1, $data['grades']['A']);
        $this->assertSame(90.88, $data['stats']['average_score']);
        $this->assertSame(array_key_first($data['grades']), 'A'); // urut dari huruf tertinggi
    }

    public function test_kaprodi_melihat_ringkasan_per_perusahaan_dan_antrean_persetujuan(): void
    {
        $data = $this->build('kaprodi@kp.test');

        $this->assertSame(0, $data['waitingApproval']); // Nadia belum diverifikasi TU
        $this->assertSame(Internship::awaitingKaprodi()->count(), $data['waitingApproval']);

        $companies = $data['companies']->keyBy(fn ($row) => $row['company']->name);
        $this->assertCount(2, $companies, 'Pengajuan yang belum berjalan tidak masuk ringkasan perusahaan');
        $this->assertSame(3, $companies['PT Nusantara Digital']['active']); // Rina, Fajar, Gilang
        $this->assertSame(2, $companies['PT Data Prima']['active']); // Dimas dan Tari
        $this->assertSame(90.88, $companies['PT Data Prima']['score']);

        $this->actingAs($this->user('kaprodi@kp.test'))->get('/dashboard')->assertOk()->assertSee('Per perusahaan');
    }

    public function test_dosen_pembimbing_melihat_penilaian_yang_menunggu_validasinya(): void
    {
        $this->actingAs($this->user('dospem@kp.test'))
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Menunggu validasi')
            ->assertSee('Fajar Pratama');

        $other = User::create(['name' => 'Dosen Lain', 'email' => 'dosen.lain@kp.test', 'password' => 'password', 'role' => 'dospem', 'is_active' => true]);
        $this->assertCount(0, app(BuildMonitoringDashboard::class)->handle($other)['waitingValidation']);
    }

    public function test_halaman_statistik_perusahaan_untuk_tu_dan_kaprodi(): void
    {
        $dataPrima = Company::where('name', 'PT Data Prima')->firstOrFail();

        $this->actingAs($this->user('tu@kp.test'))
            ->get("/perusahaan/{$dataPrima->id}")
            ->assertOk()
            ->assertSee('Sebaran skill')
            ->assertSee('Performa per kategori')
            ->assertSee('Salsa Nabila');

        $this->actingAs($this->user('kaprodi@kp.test'))->get("/perusahaan/{$dataPrima->id}")->assertOk();
        $this->actingAs($this->user('mahasiswa@kp.test'))->get("/perusahaan/{$dataPrima->id}")->assertForbidden();
        $this->actingAs($this->user('dospem@kp.test'))->get("/perusahaan/{$dataPrima->id}")->assertForbidden();
    }

    public function test_statistik_perusahaan_memuat_durasi_dan_performa_dari_nilai_tervalidasi(): void
    {
        $dataPrima = Company::where('name', 'PT Data Prima')->firstOrFail();
        Sanctum::actingAs($this->user('tu@kp.test'));

        $data = $this->getJson("/api/v1/companies/{$dataPrima->id}/stats")->assertOk()->json('data');

        $this->assertSame(1, $data['performance']['assessed_count']);
        $this->assertEquals(90.88, $data['performance']['average_final_score']);
        $this->assertSame(1, $data['performance']['grades']['A']);
        $this->assertSame(['Hard skill', 'Soft skill', 'Attitude'], array_column($data['performance']['aspects'], 'name'));
        $this->assertEquals(90, $data['performance']['aspects'][0]['average']);
        $this->assertGreaterThan(0, $data['average_duration_months']);

        // Nilai mahasiswa hanya dibuka jika sudah divalidasi (Dimas masih draft)
        $students = collect($data['students'])->keyBy('name');
        $this->assertSame('A', $students['Salsa Nabila']['letter_grade']);
        $this->assertNull($students['Dimas Aditya']['final_score']);
    }

    public function test_dashboard_tu_menampilkan_tautan_statistik(): void
    {
        $this->actingAs($this->user('tu@kp.test'))
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Statistik')
            ->assertSee('PT Data Prima');
    }

    public function test_grafik_aktivitas_per_minggu_menghitung_yang_terlihat_saja(): void
    {
        $kaprodi = $this->build('kaprodi@kp.test')['activity'];
        $supervisor = $this->build('supervisor2@kp.test')['activity'];

        $this->assertCount(BuildMonitoringDashboard::ACTIVITY_WEEKS, $kaprodi['notes']);
        $this->assertCount(BuildMonitoringDashboard::ACTIVITY_WEEKS, $kaprodi['done']);
        $this->assertCount(BuildMonitoringDashboard::ACTIVITY_WEEKS, $kaprodi['labels']);
        // Kaprodi melihat catatan semua mahasiswa, supervisor hanya mahasiswanya
        $this->assertGreaterThan(0, array_sum($kaprodi['notes']));
        $this->assertGreaterThan(0, array_sum($kaprodi['done']));
        $this->assertLessThan(array_sum($kaprodi['notes']), array_sum($supervisor['notes']));

        $this->actingAs($this->user('kaprodi@kp.test'))
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Aktivitas per minggu')
            ->assertSee('class="donut"', false)
            ->assertSee('class="line-chart"', false);
    }

    public function test_dashboard_supervisor_hanya_memantau_mahasiswanya(): void
    {
        $this->actingAs($this->user('supervisor2@kp.test'))->get('/dashboard')
            ->assertOk()
            ->assertSee('Company Supervisor')
            ->assertSee('Status task')->assertSee('class="donut"', false)
            ->assertSee('Catatan minggu ini')
            ->assertDontSee('Silabus')
            ->assertSee('Dimas Aditya')
            ->assertSee('Data Analyst Intern')
            ->assertDontSee('Rina Putri')
            ->assertDontSee('Task baru'); // supervisor tidak membuat task

        $this->actingAs($this->user('supervisor@kp.test'))->get('/dashboard')
            ->assertOk()->assertSee('Rina Putri')->assertSee('Atur meeting')->assertDontSee('Dimas Aditya');
    }

    public function test_dashboard_mahasiswa_menampilkan_rencana_dan_kemajuan_yang_bisa_diklik(): void
    {
        $this->actingAs($this->user('mahasiswa@kp.test'))->get('/dashboard')
            ->assertOk()
            ->assertSee('PT Nusantara Digital')
            ->assertSee('Divisi Engineering')
            ->assertSee('Backend Developer Intern')
            ->assertSee('Rencana kegiatan')
            ->assertSee('class="gt gt-compact gt-readonly"', false)
            ->assertSee('Kemajuan KP')
            ->assertSee('href="'.route('timesheet.index').'" class="mini-step-title"', false)
            ->assertSee('href="'.route('bimbingan.index').'" class="mini-step-title"', false)
            ->assertDontSee('Silabus')
            ->assertDontSee('Jam tercatat')
            ->assertSee('Perlu dikerjakan');

        // Gilang belum menyusun rencana kegiatan
        $this->actingAs($this->user('gilang@kp.test'))->get('/dashboard')
            ->assertOk()->assertSee('Isi rencana dulu di timesheet')->assertSee('Belum ada rencana');

        $this->actingAs($this->user('nadia@kp.test'))->get('/dashboard')
            ->assertOk()->assertSee('sedang diproses')->assertSee('Lihat Status KP');

        $this->actingAs($this->user('citra@kp.test'))->get('/dashboard')
            ->assertOk()->assertSee('Belum ada KP')->assertSee('Ajukan KP');
    }

    public function test_dashboard_tu_dan_admin(): void
    {
        $this->actingAs($this->user('tu@kp.test'))->get('/dashboard')
            ->assertOk()->assertSee('Antrean aktivasi')->assertSee('Hendra Gunawan')->assertSee('PT Data Prima')
            ->assertSee('Perlu verifikasi')->assertSee('Surat pengantar')->assertSee('Perusahaan');

        $this->actingAs($this->user('admin@kp.test'))->get('/dashboard')
            ->assertOk()->assertSee('Total akun')->assertSee('Aktivitas terbaru')->assertSee('Akun per peran');
    }
}

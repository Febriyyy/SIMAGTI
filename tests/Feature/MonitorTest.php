<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CompanyDivision;
use App\Models\Internship;
use App\Models\User;
use Database\Seeders\TestingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Pemantauan: Company Supervisor, dospem, dan kaprodi melihat kegiatan per kloter, tanpa bisa mengubahnya. */
class MonitorTest extends TestCase
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

    public function test_supervisor_melihat_identitas_akademik_dan_kloter_mahasiswanya(): void
    {
        $this->actingAs($this->user('supervisor@kp.test'))->get('/pantau')
            ->assertOk()
            ->assertSee('Rina Putri')->assertSee('Fajar Pratama')
            ->assertDontSee('Dimas Aditya')
            ->assertDontSee('Angkatan') // daftar ringkas: nama dan posisi saja
            ->assertSee('Universitas Satya Mandala')
            ->assertSee('Kloter 1')->assertSee('Kloter 2'); // satu divisi, dua periode berbeda

        // Identitas akademik lengkap ada di halaman Buka
        $this->get('/pantau/'.$this->internshipOf('mahasiswa@kp.test')->id)->assertOk()
            ->assertSee('Teknik Informatika')->assertSee('Angkatan');
    }

    public function test_kloter_dipisah_berdasarkan_periode_dalam_divisi_yang_sama(): void
    {
        $rina = $this->internshipOf('mahasiswa@kp.test');
        $fajar = $this->internshipOf('fajar@kp.test');

        $this->assertSame($rina->division_id, $fajar->division_id);
        $this->assertNotSame($rina->cohortKey(), $fajar->cohortKey());
        $this->assertStringStartsWith('Kloter ', $rina->cohortLabel());
    }

    public function test_detail_pemantauan_punya_tab_kegiatan_rencana_catatan_dan_aktivitas(): void
    {
        $rina = $this->internshipOf('mahasiswa@kp.test');
        $supervisor = $this->user('supervisor@kp.test');

        $this->actingAs($supervisor)->get("/pantau/{$rina->id}")->assertOk()
            ->assertSee('Backend Developer Intern')->assertSee('Semester')->assertSee('Campus Supervisor')
            ->assertSee('To Do')->assertDontSee('+ Tambah task');
        $this->get("/pantau/{$rina->id}?tab=timesheet")->assertOk()->assertSee('catatan kegiatan');
        $this->get("/pantau/{$rina->id}?tab=timesheet&periode=semua")->assertOk()->assertSee('Seluruh periode KP');
        $this->get("/pantau/{$rina->id}?tab=rencana")->assertOk()->assertSee('class="gt gt-readonly"', false)->assertSee('Membangun REST API dengan Laravel')->assertDontSee('+ Task')->assertDontSee('data-open="dlg-plan-', false);
        $this->get("/pantau/{$rina->id}?tab=aktivitas")->assertOk()->assertSee('aktivitas terakhir');
        $this->get("/pantau/{$rina->id}?tab=silabus")->assertOk()->assertSee('To Do'); // tab lama kembali ke Kegiatan
        $this->get("/pantau/{$rina->id}?tab=kegiatan&tampilan=list&status=completed")->assertOk()->assertSee('Task selesai');

        // Dospem melihat label "Dosen pembimbing", bukan "Campus Supervisor"
        $this->actingAs($this->user('dospem@kp.test'))->get("/pantau/{$rina->id}")->assertOk()->assertDontSee('Campus Supervisor');
    }

    public function test_akses_pemantauan_dibatasi(): void
    {
        $rina = $this->internshipOf('mahasiswa@kp.test');

        $this->actingAs($this->user('supervisor2@kp.test'))->get("/pantau/{$rina->id}")->assertForbidden();
        $this->actingAs($this->user('kaprodi@kp.test'))->get("/pantau/{$rina->id}")->assertOk();
        $this->actingAs($this->user('dospem@kp.test'))->get('/pantau')->assertOk()->assertSee('Gilang Ramadhan');
        $this->actingAs($this->user('mahasiswa@kp.test'))->get("/pantau/{$rina->id}")->assertForbidden();
        $this->actingAs($this->user('tu@kp.test'))->get('/pantau')->assertForbidden();
    }

    public function test_supervisor_mengoreksi_posisi_mahasiswanya(): void
    {
        $rina = $this->internshipOf('mahasiswa@kp.test');

        $this->actingAs($this->user('supervisor@kp.test'))->patch("/pantau/{$rina->id}/posisi", ['position' => 'Fullstack Developer Intern'])->assertRedirect();
        $this->assertSame('Fullstack Developer Intern', $rina->fresh()->position);

        $this->actingAs($this->user('supervisor2@kp.test'))->patch("/pantau/{$rina->id}/posisi", ['position' => 'x'])->assertForbidden();
        $this->actingAs($this->user('dospem@kp.test'))->patch("/pantau/{$rina->id}/posisi", ['position' => 'x'])->assertForbidden();
    }

    public function test_pencarian_dan_status_selesai(): void
    {
        $this->actingAs($this->user('kaprodi@kp.test'))->get('/pantau?cari=Rina')->assertOk()->assertSee('Rina Putri')->assertDontSee('Fajar Pratama');
        $this->get('/pantau?status=selesai')->assertOk()->assertSee('Salsa Nabila')->assertDontSee('Rina Putri');
    }

    public function test_kaprodi_membuat_penempatan_jalur_kampus_dengan_company_supervisor(): void
    {
        $citra = $this->user('citra@kp.test');
        $nusantara = Company::where('name', 'PT Nusantara Digital')->firstOrFail();
        $division = CompanyDivision::where('company_id', $nusantara->id)->firstOrFail();
        $payload = [
            'student_id' => $citra->id,
            'company_id' => $nusantara->id,
            'division_id' => $division->id,
            'lecturer_id' => $this->user('dospem@kp.test')->id,
            'position' => 'QA Intern',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonths(4)->toDateString(),
        ];

        $this->actingAs($this->user('kaprodi@kp.test'))->get('/penempatan/tambah')->assertOk()->assertSee('Company Supervisor');

        // Supervisor perusahaan lain ditolak
        $this->post('/penempatan', $payload + ['supervisor_id' => $this->user('supervisor2@kp.test')->id])->assertSessionHasErrors('supervisor_id');

        $this->post('/penempatan', $payload + ['supervisor_id' => $this->user('supervisor@kp.test')->id])->assertRedirect('/penempatan');
        $internship = $this->internshipOf('citra@kp.test');
        $this->assertSame('berjalan', $internship->status);
        $this->assertTrue($internship->isSupervisedBy($this->user('supervisor@kp.test')));
        $this->assertSame('QA Intern', $internship->position);

        $this->get('/penempatan')->assertOk()->assertSee('Citra Lestari')->assertSee('Supervisor: Anita Wulandari');
    }
}

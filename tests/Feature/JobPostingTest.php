<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Internship;
use App\Models\JobPosting;
use App\Models\User;
use Database\Seeders\TestingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Lowongan magang: dipasang TU/supervisor, dilihat mahasiswa, dan mengisi otomatis formulir pengajuan KP. */
class JobPostingTest extends TestCase
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

    public function test_tu_membuka_lowongan_dan_mahasiswa_tanpa_kp_diberi_tahu(): void
    {
        $company = Company::orderBy('id')->firstOrFail();
        $citra = $this->user('citra@kp.test');
        $this->assertFalse(Internship::where('student_id', $citra->id)->exists());

        $this->actingAs($this->user('tu@kp.test'))->get('/lowongan')->assertOk()->assertSee('Lowongan Magang');
        $this->actingAs($this->user('tu@kp.test'))->post('/lowongan', ['perusahaan' => $company->id, 'judul' => ''])->assertSessionHasErrorsIn('posting', ['judul', 'deskripsi']);
        $this->actingAs($this->user('tu@kp.test'))->post('/lowongan', [
            'perusahaan' => $company->id, 'judul' => 'Magang QA Engineer', 'deskripsi' => 'Menguji fitur aplikasi.', 'kuota' => 2,
            'batas' => today()->addDays(10)->toDateString(), 'keahlian' => 'Testing, SQL', 'buka' => 1,
        ])->assertSessionHasNoErrors();

        $posting = JobPosting::where('title', 'Magang QA Engineer')->firstOrFail();
        $this->assertSame('published', $posting->status);
        $this->assertSame(['SQL', 'Testing'], $posting->skills->pluck('name')->sort()->values()->all());
        $this->assertSame(1, $citra->notifications()->where('data', 'like', '%Magang QA Engineer%')->count());

        // Mahasiswa melihat lowongan dan formulir pengajuan terisi dari lowongan
        $this->actingAs($citra)->get('/lowongan-magang')->assertOk()->assertSee('Magang QA Engineer')->assertSee('Ajukan KP di sini');
        $this->actingAs($citra)->get('/status-kp?lowongan='.$posting->id)->assertOk()
            ->assertSee('Diisi dari lowongan')->assertSee('value="'.e($company->name).'"', false)->assertSee('value="Magang QA Engineer"', false);

        // Ditutup: tidak lagi tampil
        $this->actingAs($this->user('tu@kp.test'))->patch("/lowongan/{$posting->id}/status", ['status' => 'closed'])->assertRedirect();
        $this->actingAs($citra)->get('/lowongan-magang')->assertDontSee('data-open="job-'.$posting->id.'"', false);
    }

    public function test_mahasiswa_dengan_kp_aktif_hanya_melihat_dan_supervisor_hanya_untuk_perusahaannya(): void
    {
        $this->actingAs($this->user('fajar@kp.test'))->get('/lowongan-magang')->assertOk()->assertDontSee('Ajukan KP di sini');

        $fajar = Internship::where('student_id', $this->user('fajar@kp.test')->id)->latest('id')->firstOrFail();
        $supervisor = $fajar->supervisor;
        $other = Company::where('id', '!=', $fajar->company_id)->firstOrFail();

        $this->actingAs($supervisor)->post('/lowongan', ['perusahaan' => $other->id, 'judul' => 'X', 'deskripsi' => 'Y'])->assertSessionHasErrorsIn('posting', 'perusahaan');
        $this->actingAs($supervisor)->post('/lowongan', ['perusahaan' => $fajar->company_id, 'judul' => 'Magang Analis', 'deskripsi' => 'Analisis data.'])->assertSessionHasNoErrors();
        $this->assertSame('draft', JobPosting::where('title', 'Magang Analis')->value('status'));

        $foreign = JobPosting::where('company_id', '!=', $fajar->company_id)->firstOrFail();
        $this->actingAs($supervisor)->patch("/lowongan/{$foreign->id}/status", ['status' => 'closed'])->assertForbidden();

        $this->actingAs($this->user('dospem@kp.test'))->get('/lowongan')->assertForbidden();
        $this->actingAs($this->user('kaprodi@kp.test'))->get('/lowongan-magang')->assertForbidden();
    }

    public function test_lowongan_supervisor_diajukan_lalu_disetujui_atau_ditolak_tu(): void
    {
        $fajar = Internship::where('student_id', $this->user('fajar@kp.test')->id)->latest('id')->firstOrFail();
        $supervisor = $fajar->supervisor;
        $tu = $this->user('tu@kp.test');
        $citra = $this->user('citra@kp.test');

        // "Ajukan ke TU": belum terlihat mahasiswa, TU diberi tahu
        $this->actingAs($supervisor)->post('/lowongan', [
            'perusahaan' => $fajar->company_id, 'judul' => 'Magang DevOps', 'deskripsi' => 'Mengelola server.', 'buka' => 1,
        ])->assertSessionHasNoErrors();
        $posting = JobPosting::where('title', 'Magang DevOps')->firstOrFail();
        $this->assertSame('pending', $posting->status);
        $this->assertNotNull($posting->submitted_at);
        $this->assertSame(1, $tu->notifications()->where('data', 'like', '%Magang DevOps%')->count());
        $this->actingAs($citra)->get('/lowongan-magang')->assertDontSee('Magang DevOps');

        // Supervisor tidak bisa membuka sendiri atau meninjau
        $this->actingAs($supervisor)->patch("/lowongan/{$posting->id}/status", ['status' => 'published']);
        $this->assertSame('pending', $posting->fresh()->status);
        $this->actingAs($supervisor)->patch("/lowongan/{$posting->id}/tinjau", ['keputusan' => 'setujui'])->assertForbidden();

        // TU menolak: alasan wajib, supervisor diberi tahu dan melihat catatannya
        $this->actingAs($tu)->get('/lowongan')->assertSee('Magang DevOps')->assertSee('Setujui');
        $this->actingAs($tu)->patch("/lowongan/{$posting->id}/tinjau", ['keputusan' => 'tolak'])->assertSessionHasErrorsIn('review'.$posting->id, ['catatan']);
        $this->actingAs($tu)->patch("/lowongan/{$posting->id}/tinjau", ['keputusan' => 'tolak', 'catatan' => 'Lengkapi kualifikasi.'])->assertSessionHasNoErrors();
        $posting->refresh();
        $this->assertSame('rejected', $posting->status);
        $this->assertSame($tu->id, $posting->reviewed_by);
        $this->assertSame(1, $supervisor->notifications()->where('data', 'like', '%perlu perbaikan%')->count());
        $this->actingAs($supervisor)->get('/lowongan')->assertSee('Lengkapi kualifikasi.')->assertSee('Ajukan lagi');

        // Ajukan lagi lalu disetujui: dibuka dan mahasiswa tanpa KP diberi tahu
        $this->actingAs($supervisor)->patch("/lowongan/{$posting->id}/status", ['status' => 'published']);
        $this->assertSame('pending', $posting->fresh()->status);
        $this->assertNull($posting->fresh()->review_note);
        $this->actingAs($tu)->patch("/lowongan/{$posting->id}/tinjau", ['keputusan' => 'setujui'])->assertSessionHasNoErrors();
        $this->assertSame('published', $posting->fresh()->status);
        $this->actingAs($citra)->get('/lowongan-magang')->assertSee('data-open="job-'.$posting->id.'"', false);
        $this->assertSame(1, $citra->notifications()->where('data', 'like', '%Magang DevOps%')->count());

        // Supervisor mengubah lowongan yang sudah dibuka: ditinjau ulang TU
        $this->actingAs($supervisor)->put("/lowongan/{$posting->id}", [
            'perusahaan' => $fajar->company_id, 'judul' => 'Magang DevOps', 'deskripsi' => 'Mengelola server dan CI/CD.',
        ])->assertSessionHasNoErrors();
        $this->assertSame('pending', $posting->fresh()->status);
        $this->actingAs($citra)->get('/lowongan-magang')->assertDontSee('data-open="job-'.$posting->id.'"', false);

        // Lowongan yang tidak menunggu persetujuan tidak bisa ditinjau
        $this->actingAs($tu)->patch("/lowongan/{$posting->id}/tinjau", ['keputusan' => 'setujui']);
        $this->actingAs($tu)->patch("/lowongan/{$posting->id}/tinjau", ['keputusan' => 'setujui'])->assertNotFound();
    }
}

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
        $this->actingAs($citra)->get('/lowongan-magang')->assertDontSee('<h2 class="job-card-title">Magang QA Engineer</h2>', false);
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
}

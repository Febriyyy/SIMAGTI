<?php

namespace Tests\Feature;

use App\Models\Internship;
use App\Models\Portfolio;
use App\Models\PortfolioCertificate;
use App\Models\PortfolioProject;
use App\Models\User;
use Database\Seeders\TestingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Portofolio mahasiswa: diisi sejak sebelum magang, dilihat pihak terkait, dan bisa dibagikan lewat tautan publik. */
class PortfolioTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TestingSeeder::class);
        Storage::fake('local');
    }

    private function user(string $email): User
    {
        return User::where('email', $email)->firstOrFail();
    }

    /** Mahasiswa baru tanpa KP sama sekali. */
    private function freshStudent(): User
    {
        return User::create(['name' => 'Rania Putri', 'email' => 'rania@kp.test', 'password' => 'password', 'role' => 'mahasiswa', 'is_active' => true]);
    }

    public function test_mahasiswa_tanpa_kp_bisa_mengisi_profil_minat_ipk_dan_skill(): void
    {
        $rania = $this->freshStudent();
        $this->actingAs($rania)->get('/portofolio')->assertOk()->assertSee('Tentang saya')->assertSee('Muncul otomatis setelah kamu mendapat tempat magang.');

        $this->actingAs($rania)->patch('/portofolio', [
            'headline' => 'Mahasiswa Informatika yang suka membangun API',
            'about' => 'Senang backend dan data.',
            'gpa' => '3.67', 'show_gpa' => '1', 'is_public' => '0',
            'interests' => ['Backend', 'Data', 'Bukan pilihan'],
            'skill_name' => ['Laravel', ' laravel ', 'Python', ''],
            'skill_level' => ['mahir', 'pemula', 'menengah', 'pemula'],
        ])->assertSessionHasErrors('interests.2');

        $this->actingAs($rania)->patch('/portofolio', [
            'headline' => 'Mahasiswa Informatika yang suka membangun API',
            'about' => 'Senang backend dan data.',
            'gpa' => '3.67', 'show_gpa' => '1', 'is_public' => '0',
            'interests' => ['Backend', 'Data'],
            'skill_name' => ['Laravel', ' laravel ', 'Python', ''],
            'skill_level' => ['mahir', 'pemula', 'menengah', 'pemula'],
        ])->assertRedirect(route('portofolio.edit'))->assertSessionHasNoErrors();

        $portfolio = Portfolio::where('user_id', $rania->id)->firstOrFail();
        $this->assertSame(['Backend', 'Data'], $portfolio->interests);
        $this->assertTrue($portfolio->show_gpa);
        $this->assertEquals(3.67, (float) $rania->fresh()->studentProfile->gpa);
        $skills = $rania->fresh()->skills->mapWithKeys(fn ($s) => [$s->name => $s->pivot->level])->all();
        $this->assertSame(['Laravel' => 'mahir', 'Python' => 'menengah'], collect($skills)->sortKeys()->all(), 'Skill tidak dobel; tingkat pertama yang dipakai');
    }

    public function test_proyek_dan_sertifikat_dengan_berkas(): void
    {
        $rania = $this->freshStudent();

        $this->actingAs($rania)->post('/portofolio/proyek', [
            'title' => 'Aplikasi Tiket Bioskop', 'role' => 'Backend', 'year' => 2025,
            'tech' => 'Laravel, MySQL, Laravel', 'url' => 'https://github.com/rania/tiket',
            'image' => UploadedFile::fake()->image('tiket.png', 600, 400),
        ])->assertRedirect(route('portofolio.edit').'#proyek')->assertSessionHasNoErrors();
        $project = PortfolioProject::firstOrFail();
        $this->assertSame('Laravel, MySQL', $project->tech);
        $this->assertNotNull($project->image_file_id);

        $this->actingAs($rania)->post('/portofolio/proyek', ['title' => 'Tanpa tautan sah', 'url' => 'javascript:alert(1)'], ['Accept' => 'text/html'])
            ->assertSessionHasErrorsIn('project', 'url');

        $this->actingAs($rania)->post('/portofolio/sertifikat', [
            'name' => 'Bootcamp Fullstack', 'issuer' => 'Dicoding', 'issued_on' => '2025-06-01',
            'credential_url' => 'https://dicoding.com/c/123',
            'file' => UploadedFile::fake()->create('sertifikat.pdf', 200, 'application/pdf'),
        ])->assertSessionHasNoErrors();
        $cert = PortfolioCertificate::firstOrFail();
        $this->assertNotNull($cert->file_id);

        // Berkas bisa dibuka pemiliknya, tidak oleh mahasiswa lain
        $this->actingAs($rania)->get(route('portofolio.berkas', $cert->file_id))->assertOk();
        $this->actingAs($this->user('salsa@kp.test'))->get(route('portofolio.berkas', $cert->file_id))->assertForbidden();
        $this->actingAs($this->user('salsa@kp.test'))->post(route('portofolio.proyek.update', $project), ['title' => 'Diambil alih'])->assertForbidden();

        // Ubah lalu hapus
        $this->actingAs($rania)->post(route('portofolio.proyek.update', $project), ['title' => 'Aplikasi Tiket', 'remove_image' => '1'])->assertSessionHasNoErrors();
        $this->assertNull($project->fresh()->image_file_id);
        $this->actingAs($rania)->delete(route('portofolio.sertifikat.destroy', $cert))->assertRedirect();
        $this->assertDatabaseMissing('portfolio_certificates', ['id' => $cert->id]);
    }

    public function test_tautan_publik_hanya_bila_dinyalakan_dan_ipk_hanya_bila_diizinkan(): void
    {
        $rania = $this->freshStudent();
        $this->actingAs($rania)->post('/portofolio/proyek', ['title' => 'Dashboard Penjualan', 'image' => UploadedFile::fake()->image('d.png')]);
        $this->actingAs($rania)->patch('/portofolio', ['gpa' => '3.45', 'show_gpa' => '0', 'is_public' => '0', 'skill_name' => ['SQL'], 'skill_level' => ['mahir']]);
        $portfolio = Portfolio::where('user_id', $rania->id)->firstOrFail();

        auth()->logout();
        $this->get("/p/{$portfolio->token}")->assertNotFound();

        $this->actingAs($rania)->patch('/portofolio', ['gpa' => '3.45', 'show_gpa' => '0', 'is_public' => '1', 'skill_name' => ['SQL'], 'skill_level' => ['mahir']]);
        auth()->logout();
        $this->get("/p/{$portfolio->token}")->assertOk()
            ->assertSee('Rania Putri')->assertSee('Dashboard Penjualan')->assertSee('SQL')
            ->assertDontSee('3,45')->assertHeader('X-Robots-Tag', 'noindex');

        $image = PortfolioProject::firstOrFail()->image_file_id;
        $this->get(route('portofolio.publik.berkas', [$portfolio->token, $image]))->assertOk();
        $this->get(route('portofolio.publik.berkas', [str_repeat('a', 32), $image]))->assertNotFound();

        $this->actingAs($rania)->patch('/portofolio', ['gpa' => '3.45', 'show_gpa' => '1', 'is_public' => '1']);
        auth()->logout();
        $this->get("/p/{$portfolio->token}")->assertOk()->assertSee('3,45');
    }

    public function test_pihak_kampus_dosen_dan_supervisor_terkait_bisa_melihat(): void
    {
        $internship = Internship::whereNotNull('lecturer_id')->whereHas('staff')->with('student', 'lecturer')->firstOrFail();
        $student = $internship->student;
        $supervisor = $internship->supervisor;

        $this->actingAs($this->user('kaprodi@kp.test'))->get(route('portofolio.lihat', $student))->assertOk()->assertSee($student->name);
        $this->actingAs($this->user('tu@kp.test'))->get(route('portofolio.lihat', $student))->assertOk();
        $this->actingAs($internship->lecturer)->get(route('portofolio.lihat', $student))->assertOk();
        $this->actingAs($supervisor)->get(route('portofolio.lihat', $student))->assertOk();
        $this->actingAs($this->freshStudent())->get(route('portofolio.lihat', $student))->assertForbidden();

        // Supervisor perusahaan lain tidak bisa melihat
        $other = User::where('role', 'supervisor')->where('id', '!=', $supervisor->id)->get()
            ->first(fn ($u) => ! (new Portfolio(['user_id' => $student->id]))->canBeViewedBy($u));
        if ($other) {
            $this->actingAs($other)->get(route('portofolio.lihat', $student))->assertForbidden();
        }
    }

    public function test_supervisor_melihat_portofolio_mahasiswa_yang_mengajukan_ke_perusahaannya(): void
    {
        $supervisor = $this->user('supervisor@kp.test');
        $companyId = Internship::supervisedBy($supervisor)->value('company_id');
        $application = Internship::where('status', 'diajukan')->firstOrFail();
        $application->update(['company_id' => $companyId]);
        $applicant = $application->student;

        $this->actingAs($supervisor)->get(route('lowongan.kelola'))->assertOk()->assertSee('Pengajuan masuk')->assertSee($applicant->name);
        $this->actingAs($supervisor)->get(route('portofolio.lihat', $applicant))->assertOk()->assertSee($applicant->name);
    }

    public function test_pengalaman_magang_menampilkan_tempat_dan_supervisor(): void
    {
        $internship = Internship::where('status', 'berjalan')->whereHas('staff')->firstOrFail();
        $student = $internship->student;

        $this->actingAs($student)->get(route('portofolio.lihat', $student))->assertOk()
            ->assertSee('Pengalaman')->assertSee($internship->placeName())->assertSee($internship->supervisor->name);
    }

    public function test_hanya_mahasiswa_yang_mengatur_portofolio(): void
    {
        $this->actingAs($this->user('dospem@kp.test'))->get('/portofolio')->assertForbidden();
        $this->actingAs($this->user('kaprodi@kp.test'))->get(route('portofolio.lihat', $this->user('dospem@kp.test')))->assertNotFound();
    }

    public function test_minat_lainnya_bisa_ditulis_sendiri_maksimal_lima(): void
    {
        $rania = $this->freshStudent();
        $this->actingAs($rania)->patch('/portofolio', ['interests' => ['Backend'], 'interest_other' => 'Game Development, robotika, Game development'])
            ->assertSessionHasNoErrors();
        $this->assertSame(['Backend', 'Game Development', 'robotika'], Portfolio::where('user_id', $rania->id)->firstOrFail()->interests);

        $this->actingAs($rania)->patch('/portofolio', ['interests' => ['Backend', 'Data', 'Mobile'], 'interest_other' => 'Game, Robotika, IoT'])
            ->assertSessionHasErrors('interests');
    }

    public function test_proyek_dan_sertifikat_bisa_punya_banyak_tautan_berlabel(): void
    {
        $rania = $this->freshStudent();
        $this->actingAs($rania)->post('/portofolio/proyek', [
            'title' => 'Aplikasi Kasir',
            'links' => ['https://github.com/rania/kasir', 'https://kasir.vercel.app', 'https://www.figma.com/file/abc', '', 'https://github.com/rania/kasir'],
        ])->assertSessionHasNoErrors();
        $project = PortfolioProject::firstOrFail();
        $this->assertCount(3, $project->links);
        $this->assertSame(['GitHub', 'Demo', 'Figma'], array_column($project->linkList(), 'label'));

        $this->actingAs($rania)->post('/portofolio/proyek', ['title' => 'Salah', 'links' => ['bukan tautan']])->assertSessionHasErrorsIn('project', 'links.0');

        $this->actingAs($rania)->post('/portofolio/sertifikat', [
            'name' => 'Cloud Practitioner', 'issuer' => 'AWS',
            'links' => ['https://www.credly.com/badges/123', 'https://drive.google.com/file/d/xyz'],
        ])->assertSessionHasNoErrors();
        $this->assertSame(['Credly', 'Google Drive'], array_column(PortfolioCertificate::firstOrFail()->linkList(), 'label'));

        $this->actingAs($rania)->get(route('portofolio.lihat', $rania))->assertOk()->assertSee('GitHub')->assertSee('Figma')->assertSee('Google Drive');
    }
}

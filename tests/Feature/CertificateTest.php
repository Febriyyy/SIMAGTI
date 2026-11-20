<?php

namespace Tests\Feature;

use App\Http\Controllers\DivisionHeadController;
use App\Models\Certificate;
use App\Models\Internship;
use App\Models\User;
use Database\Seeders\TestingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Sertifikat KP: halaman cetak dengan QR dan halaman verifikasi publik. */
class CertificateTest extends TestCase
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

    private function internshipOf(string $studentEmail): Internship
    {
        return Internship::where('student_id', $this->user($studentEmail)->id)->latest('id')->firstOrFail();
    }

    public function test_mahasiswa_dan_dosen_membuka_sertifikat_kp_yang_diterima(): void
    {
        $salsa = $this->internshipOf('salsa@kp.test');
        $certificate = $salsa->certificate;
        $this->assertSame('diterima', $salsa->result);

        foreach (['salsa@kp.test', 'dospem@kp.test', 'kaprodi@kp.test'] as $email) {
            $this->actingAs($this->user($email))->get("/sertifikat/{$salsa->id}")->assertOk()
                ->assertSee('Kerja Praktik')
                ->assertSee('Salsa Nabila')
                ->assertSee($certificate->number)
                ->assertSee('<svg', false)
                ->assertSee(route('sertifikat.verifikasi', $certificate->verification_token))
                ->assertSee('Cetak / Simpan PDF');
        }

        // Tautan sertifikat tampil di halaman Status KP mahasiswa
        $this->actingAs($this->user('salsa@kp.test'))->get('/status-kp')->assertSee(route('sertifikat.cetak', $salsa));
    }

    public function test_sertifikat_tidak_bisa_dibuka_orang_lain_atau_kp_yang_belum_diterima(): void
    {
        $salsa = $this->internshipOf('salsa@kp.test');

        $this->actingAs($this->user('mahasiswa@kp.test'))->get("/sertifikat/{$salsa->id}")->assertForbidden();
        auth()->logout();
        $this->get("/sertifikat/{$salsa->id}")->assertRedirect(route('login'));

        $rina = $this->internshipOf('mahasiswa@kp.test');
        $this->actingAs($this->user('mahasiswa@kp.test'))->get("/sertifikat/{$rina->id}")->assertNotFound();

        $salsa->certificate->update(['status' => 'dicabut', 'revoked_at' => now(), 'revoked_reason' => 'Salah data']);
        $this->actingAs($this->user('salsa@kp.test'))->get("/sertifikat/{$salsa->id}")->assertNotFound();
    }

    public function test_hasil_diterima_langsung_menerbitkan_sertifikat(): void
    {
        $fajar = $this->internshipOf('fajar@kp.test');
        $dospem = $this->user('dospem@kp.test');

        $this->actingAs($dospem)->patch("/validasi/{$fajar->id}", ['keputusan' => 'validasi', 'catatan' => 'Sesuai.']);
        $this->actingAs($dospem)->patch("/hasil/{$fajar->id}", ['result' => 'diterima', 'result_note' => 'Baik.'])->assertRedirect('/hasil');

        $certificate = $fajar->fresh()->certificate;
        $this->assertNotNull($certificate);
        $this->assertTrue($certificate->isValid());
        $this->assertMatchesRegularExpression('#^KP/\d{4}/\d{4}$#', $certificate->number);

        $this->get("/hasil/{$fajar->id}")->assertSee(route('sertifikat.cetak', $fajar));
        $this->get("/sertifikat/{$fajar->id}")->assertOk()->assertSee($certificate->number);
        $this->assertSame(1, Certificate::where('internship_id', $fajar->id)->count(), 'Membuka ulang tidak membuat sertifikat baru');
    }

    public function test_verifikasi_publik_tanpa_login_dan_tanpa_nim(): void
    {
        $salsa = $this->internshipOf('salsa@kp.test');
        $certificate = $salsa->certificate;

        $this->get("/sertifikat/verifikasi/{$certificate->verification_token}")->assertOk()
            ->assertSee('Sertifikat sah')
            ->assertSee('Salsa Nabila')
            ->assertSee($certificate->number)
            ->assertSee('class="vx-sheet"', false)   // sertifikat tampilan potret
            ->assertSee('Keterangan nilai')
            ->assertSee('data-vx-tab="nilai"', false)   // nilai lengkap di tab terpisah, muncul saat diklik
            ->assertDontSee('2310511045')
            ->assertHeader('X-Robots-Tag', 'noindex');

        $certificate->update(['status' => 'dicabut', 'revoked_at' => now(), 'revoked_reason' => 'Salah data']);
        $this->get("/sertifikat/verifikasi/{$certificate->verification_token}")->assertOk()
            ->assertSee('Sertifikat tidak berlaku')->assertSee('Salah data')
            ->assertDontSee('Keterangan nilai'); // nilai hanya untuk sertifikat yang sah

        $this->get('/sertifikat/verifikasi/'.str_repeat('x', 48))->assertNotFound()->assertSee('Sertifikat tidak ditemukan');
    }

    public function test_logo_kampus_logo_perusahaan_dan_ketua_divisi_tampil_di_sertifikat(): void
    {
        Storage::fake('local');
        $salsa = $this->internshipOf('salsa@kp.test');
        $company = $salsa->company;
        $division = $salsa->division ?? $company->divisions()->firstOrCreate(['name' => 'Data']);
        $salsa->update(['division_id' => $division->id]);

        // Tanpa logo: sertifikat memakai logo SIMAGTI dan titik-titik untuk ketua divisi
        $this->actingAs($salsa->student)->get("/sertifikat/{$salsa->id}")->assertOk()
            ->assertSee('logo-simagti.svg', false)->assertSee('Ketua Divisi '.$division->name)->assertDontSee(route('logo.kampus'));

        // Admin mengunggah logo kampus (SVG ditolak demi keamanan)
        $admin = $this->user('admin@kp.test');
        $this->actingAs($admin)->get('/admin/kampus')->assertOk()->assertSee('Logo kampus');
        $this->actingAs($admin)->put('/admin/kampus', ['logo' => UploadedFile::fake()->create('logo.svg', 5, 'image/svg+xml')])->assertSessionHasErrors('logo');
        $this->actingAs($admin)->put('/admin/kampus', ['logo' => UploadedFile::fake()->image('kampus.png', 300, 300)])->assertSessionHasNoErrors();
        $this->get('/logo/kampus')->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->actingAs($salsa->student)->put('/admin/kampus', ['logo' => UploadedFile::fake()->image('x.png')])->assertForbidden();

        // TU mengunggah logo perusahaan
        $this->actingAs($this->user('tu@kp.test'))->patch("/perusahaan/{$company->id}", [
            'name' => $company->name,
            'logo' => UploadedFile::fake()->image('perusahaan.png', 400, 200),
        ])->assertSessionHasNoErrors();
        $this->assertNotNull($company->fresh()->logo_file_id);
        $this->get("/logo/perusahaan/{$company->id}")->assertOk();

        // Company Supervisor mengisi nama dan tanda tangan ketua divisi
        $supervisor = $salsa->supervisor;
        $this->actingAs($supervisor)->get('/ketua-divisi')->assertOk()->assertSee('Divisi '.$division->name);
        $this->actingAs($supervisor)->put("/ketua-divisi/{$division->id}", ['head_name' => ''])->assertSessionHasErrorsIn('divisi'.$division->id, 'head_name');
        $this->actingAs($supervisor)->put("/ketua-divisi/{$division->id}", [
            'head_name' => 'Rahmat Hidayat',
            'signature' => UploadedFile::fake()->image('ttd.png', 400, 160),
        ])->assertSessionHasNoErrors();
        $this->assertSame('Rahmat Hidayat', $division->fresh()->head_name);
        $this->assertNotNull($division->fresh()->head_signature_file_id);

        // Supervisor perusahaan lain dan TU tidak bisa mengubah divisi ini
        $other = User::where('role', 'supervisor')->where('id', '!=', $supervisor->id)->first();
        if ($other && ! DivisionHeadController::divisionsFor($other)->contains('id', $division->id)) {
            $this->actingAs($other)->put("/ketua-divisi/{$division->id}", ['head_name' => 'Orang lain'])->assertForbidden();
        }
        $this->actingAs($this->user('tu@kp.test'))->get('/ketua-divisi')->assertForbidden();

        $this->actingAs($salsa->student)->get("/sertifikat/{$salsa->id}")->assertOk()
            ->assertSee(route('logo.kampus'), false)
            ->assertSee(route('logo.perusahaan', $company), false)
            ->assertSee('Rahmat Hidayat')
            ->assertSee('data:image/png;base64,', false)
            ->assertSee('Ketua Program Studi');

        // Hapus logo perusahaan
        $this->actingAs($this->user('tu@kp.test'))->patch("/perusahaan/{$company->id}", ['name' => $company->name, 'remove_logo' => 1])->assertSessionHasNoErrors();
        $this->assertNull($company->fresh()->logo_file_id);
        $this->get("/logo/perusahaan/{$company->id}")->assertNotFound();
    }
}

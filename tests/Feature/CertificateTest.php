<?php

namespace Tests\Feature;

use App\Http\Controllers\DivisionHeadController;
use App\Http\Controllers\KaprodiSignatureController;
use App\Models\Certificate;
use App\Models\Internship;
use App\Models\User;
use App\Support\Logos;
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

    public function test_logo_tanda_tangan_dan_penanda_tangan_lengkap_di_sertifikat(): void
    {
        Storage::fake('local');
        $salsa = $this->internshipOf('salsa@kp.test');
        $company = $salsa->company;
        $division = $salsa->division ?? $company->divisions()->firstOrCreate(['name' => 'Data']);
        $salsa->update(['division_id' => $division->id]);
        $png = fn (string $name, int $w = 300, int $h = 160) => UploadedFile::fake()->image($name, $w, $h);

        // Tanpa data: logo SIMAGTI dan garis kosong
        $this->actingAs($salsa->student)->get("/sertifikat/{$salsa->id}")->assertOk()
            ->assertSee('Unggah logo')->assertSee('Ketua Divisi '.$division->name)
            ->assertDontSee(route('logo.kampus'))->assertDontSee('data:image', false);

        // Kaprodi: logo kampus dan logo fakultas (SVG ditolak); admin tidak lagi punya menu Identitas Kampus
        $admin = $this->user('admin@kp.test');
        $kaprodiLogo = $this->user('kaprodi@kp.test');
        $this->actingAs($admin)->get('/dashboard')->assertOk()->assertDontSee('Identitas Kampus');
        // Halaman admin Kampus & Prodi hanya identitas teks dan prodi; logo tetap diunggah kaprodi
        $this->actingAs($admin)->get('/admin/kampus')->assertOk()->assertSee('Identitas kampus')->assertDontSee('Logo kampus');
        $this->actingAs($admin)->put('/kaprodi/tanda-tangan/logo/kampus', ['logo' => $png('x.png')])->assertForbidden();
        $this->actingAs($kaprodiLogo)->get('/kaprodi/tanda-tangan')->assertOk()->assertSee('Logo kampus')->assertSee('Logo fakultas');
        $this->actingAs($kaprodiLogo)->put('/kaprodi/tanda-tangan/logo/kampus', ['logo' => UploadedFile::fake()->create('logo.svg', 5, 'image/svg+xml')])->assertSessionHasErrorsIn('logo_kampus', 'logo');
        $this->actingAs($kaprodiLogo)->put('/kaprodi/tanda-tangan/logo/kampus', ['logo' => $png('kampus.png')])->assertSessionHasNoErrors();
        $this->actingAs($kaprodiLogo)->put('/kaprodi/tanda-tangan/logo/fakultas', ['logo' => $png('fakultas.png')])->assertSessionHasNoErrors();
        $this->get('/logo/kampus')->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->get('/logo/fakultas')->assertOk();
        $this->actingAs($salsa->student)->put('/kaprodi/tanda-tangan/logo/kampus', ['logo' => $png('x.png')])->assertForbidden();

        // Company Supervisor: logo perusahaan, nama dan tanda tangan ketua divisi
        $supervisor = $salsa->supervisor;
        $this->actingAs($supervisor)->get('/data-sertifikat')->assertOk()->assertSee('Logo perusahaan')->assertSee($company->name)->assertSee('Divisi '.$division->name);
        $this->actingAs($supervisor)->put("/data-sertifikat/perusahaan/{$company->id}", ['logo' => $png('perusahaan.png', 400, 200)])->assertSessionHasNoErrors();
        $this->assertNotNull($company->fresh()->logo_file_id);
        $this->actingAs($supervisor)->put("/data-sertifikat/divisi/{$division->id}", ['head_name' => ''])->assertSessionHasErrorsIn('divisi'.$division->id, 'head_name');
        $this->actingAs($supervisor)->put("/data-sertifikat/divisi/{$division->id}", ['head_name' => 'Rahmat Hidayat', 'signature' => $png('ttd.png')])->assertSessionHasNoErrors();
        $this->assertSame('Rahmat Hidayat', $division->fresh()->head_name);

        // TU tidak lagi mengunggah logo perusahaan; supervisor lain tidak bisa mengubah
        $this->actingAs($this->user('tu@kp.test'))->get('/data-sertifikat')->assertForbidden();
        $this->actingAs($this->user('tu@kp.test'))->get("/perusahaan/{$company->id}/ubah")->assertOk()->assertDontSee('Logo perusahaan');
        $other = User::where('role', 'supervisor')->where('id', '!=', $supervisor->id)->get()
            ->first(fn (User $u) => ! DivisionHeadController::companiesFor($u)->contains('id', $company->id));
        if ($other) {
            $this->actingAs($other)->put("/data-sertifikat/perusahaan/{$company->id}", ['logo' => $png('x.png')])->assertForbidden();
            $this->actingAs($other)->put("/data-sertifikat/divisi/{$division->id}", ['head_name' => 'Orang lain'])->assertForbidden();
        }

        // Kaprodi: nama, jabatan, dan tanda tangan sendiri
        $kaprodi = $this->user('kaprodi@kp.test');
        $this->actingAs($kaprodi)->get('/kaprodi/tanda-tangan')->assertOk()->assertSee('Tanda tangan');
        $this->actingAs($kaprodi)->put('/kaprodi/tanda-tangan', ['signer_name' => 'Dr. Kaprodi Uji, M.Kom.', 'signer_title' => 'Ketua Program Studi', 'signature' => $png('ttd-kaprodi.png')])->assertSessionHasNoErrors();
        $this->actingAs($supervisor)->get('/kaprodi/tanda-tangan')->assertForbidden();

        // Semuanya tampil di sertifikat: tiga logo dan dua tanda tangan
        $page = $this->actingAs($salsa->student)->get("/sertifikat/{$salsa->id}")->assertOk()
            ->assertSee(route('logo.kampus'), false)
            ->assertSee(route('logo.fakultas'), false)
            ->assertSee(route('logo.perusahaan', $company), false)
            ->assertSee('Rahmat Hidayat')
            ->assertSee('Dr. Kaprodi Uji, M.Kom.');
        $this->assertSame(2, substr_count($page->getContent(), 'data:image/png;base64,'));

        // Logo perusahaan bisa dihapus supervisor
        $this->actingAs($supervisor)->delete("/data-sertifikat/perusahaan/{$company->id}")->assertRedirect();
        $this->assertNull($company->fresh()->logo_file_id);
        $this->get("/logo/perusahaan/{$company->id}")->assertNotFound();
    }

    public function test_status_kelengkapan_tanda_tangan_antara_kaprodi_dan_perusahaan(): void
    {
        Storage::fake('local');
        $salsa = $this->internshipOf('salsa@kp.test');
        $company = $salsa->company;
        $division = $salsa->division ?? $company->divisions()->firstOrCreate(['name' => 'Data']);
        $salsa->update(['division_id' => $division->id]);
        $supervisor = $salsa->supervisor;
        $kaprodi = $this->user('kaprodi@kp.test');
        $png = fn (string $name, int $w = 300, int $h = 160) => UploadedFile::fake()->image($name, $w, $h);

        // Company Supervisor melihat status tanda tangan kaprodi, bukan gambarnya
        $this->actingAs($supervisor)->get('/data-sertifikat')->assertOk()->assertSee('Belum ditandatangani kaprodi');
        $this->actingAs($kaprodi)->put('/kaprodi/tanda-tangan', ['signer_name' => 'Dr. Kaprodi Uji, M.Kom.', 'signer_title' => 'Ketua Program Studi', 'signature' => $png('ttd-kaprodi.png')])->assertSessionHasNoErrors();
        $this->actingAs($supervisor)->get('/data-sertifikat')->assertOk()
            ->assertSee('Sudah ditandatangani kaprodi')->assertSee('Dr. Kaprodi Uji, M.Kom.')
            ->assertDontSee('data:image', false);

        // Kaprodi melihat kelengkapan per divisi; divisi yang belum lengkap ditandai
        $this->actingAs($kaprodi)->get('/kaprodi/tanda-tangan')->assertOk()
            ->assertSee('Kelengkapan dari perusahaan')->assertSee($company->name)->assertSee('Divisi '.$division->name);
        $row = fn () => collect(KaprodiSignatureController::companyCompleteness())->first(fn ($r) => $r['division']->is($division));
        $this->assertFalse($row()['complete']);

        $this->actingAs($supervisor)->put("/data-sertifikat/perusahaan/{$company->id}", ['logo' => $png('logo.png')])->assertSessionHasNoErrors();
        $this->actingAs($supervisor)->put("/data-sertifikat/divisi/{$division->id}", ['head_name' => 'Rahmat Hidayat', 'signature' => $png('ttd.png', 420, 140)])->assertSessionHasNoErrors();
        $this->assertTrue($row()['complete']);
        $divisionSignature = Logos::dataUri($division->fresh()->headSignature);
        $this->assertNotNull($divisionSignature);
        $this->actingAs($kaprodi)->get('/kaprodi/tanda-tangan')->assertOk()->assertSee('Rahmat Hidayat')->assertDontSee($divisionSignature, false)
            ->assertSee('cpl-row is-complete', false);
    }
}

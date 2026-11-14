<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\Internship;
use App\Models\User;
use Database\Seeders\TestingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}

<?php

namespace Tests\Feature;

use App\Actions\Reminders\SendReminders;
use App\Models\Internship;
use App\Models\User;
use Database\Seeders\TestingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Rekomendasi jadi karyawan: supervisor merekomendasikan lalu mengirim tawaran resmi (detail + surat PDF + batas waktu),
 * mahasiswa membaca lalu menerima atau menolak dengan alasan; tanpa jawaban sampai batas waktu menjadi kedaluwarsa.
 */
class ConversionTest extends TestCase
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

    private function internshipOf(string $email): Internship
    {
        return Internship::where('student_id', $this->user($email)->id)->latest('id')->firstOrFail();
    }

    /** @return array<string, mixed> */
    private function offer(array $overrides = []): array
    {
        return $overrides + [
            'posisi' => 'Junior Backend Developer',
            'unit' => 'Tim Platform',
            'lokasi' => 'Bandung',
            'mode' => 'hybrid',
            'jenis' => 'kontrak',
            'durasi' => 12,
            'mulai' => now()->addMonth()->toDateString(),
            'deskripsi' => "Mengembangkan API layanan pemesanan\nMenulis pengujian otomatis",
            'kompensasi' => 'Dibahas saat wawancara',
            'hr_nama' => 'Rani HR',
            'hr_email' => 'hr@perusahaan.test',
            'batas' => now()->addDays(7)->toDateString(),
            'surat' => UploadedFile::fake()->create('surat-penawaran.pdf', 200, 'application/pdf'),
        ];
    }

    private function recommended(): Internship
    {
        $fajar = $this->internshipOf('fajar@kp.test');
        $this->actingAs($fajar->supervisor)->post("/pantau/{$fajar->id}/rekrutmen", ['langkah' => 'rekomendasikan', 'catatan' => 'Cepat belajar'])->assertSessionHasNoErrors();

        return $fajar->fresh();
    }

    public function test_tawaran_resmi_dibaca_lalu_diterima_mahasiswa(): void
    {
        $fajar = $this->recommended();
        $supervisor = $fajar->supervisor;
        $student = $this->user('fajar@kp.test');

        // Sebelum ditawari, mahasiswa belum melihat apa pun
        $this->actingAs($student)->get('/tawaran-kerja')->assertNotFound();

        // Surat dan detail wajib
        $this->actingAs($supervisor)->post("/pantau/{$fajar->id}/rekrutmen/tawaran", $this->offer(['surat' => null, 'deskripsi' => '']))
            ->assertSessionHasErrorsIn('offer', ['surat', 'deskripsi']);
        $this->actingAs($supervisor)->post("/pantau/{$fajar->id}/rekrutmen/tawaran", $this->offer())->assertSessionHasNoErrors();

        $c = $fajar->fresh()->conversion;
        $this->assertSame('ditawari', $c->status);
        $this->assertSame('Bandung', $c->work_location);
        $this->assertSame(['Mengembangkan API layanan pemesanan', 'Menulis pengujian otomatis'], $c->jobPoints());
        $this->assertNotNull($c->offer_letter_id);

        // Dashboard: satu tombol menuju detail; halaman detail memuat informasi dan surat
        $this->actingAs($student)->get('/dashboard')->assertSee('Lihat tawaran')->assertDontSee('Terima tawaran');
        $this->actingAs($student)->get('/tawaran-kerja')->assertOk()
            ->assertSee('Junior Backend Developer')->assertSee('Karyawan kontrak · 12 bulan')->assertSee('Menulis pengujian otomatis')
            ->assertSee('Terima tawaran')->assertSee('Tolak tawaran')->assertSee('7 hari lagi');
        $this->actingAs($student)->get("/rekrutmen/{$c->id}/surat")->assertOk()->assertHeader('Content-Type', 'application/pdf');

        // Dosen pembimbing tidak membuka surat (urusan perusahaan dan mahasiswa)
        $this->actingAs($fajar->lecturer)->get("/rekrutmen/{$c->id}/surat")->assertForbidden();
        $this->actingAs($fajar->lecturer)->get("/pantau/{$fajar->id}")->assertSee('Rekrutmen')->assertDontSee('Perpanjang batas');

        $this->actingAs($student)->patch('/tawaran-kerja', ['jawaban' => 'terima'])->assertSessionHasNoErrors();
        $c->refresh();
        $this->assertSame('diterima', $c->status);
        $this->assertNotNull($c->responded_at);
        $this->assertSame(['direkomendasikan', 'ditawari', 'diterima'], $c->histories()->orderBy('id')->pluck('to_status')->all());
    }

    public function test_menolak_wajib_dengan_alasan(): void
    {
        $fajar = $this->recommended();
        $this->actingAs($fajar->supervisor)->post("/pantau/{$fajar->id}/rekrutmen/tawaran", $this->offer());
        $student = $this->user('fajar@kp.test');

        $this->actingAs($student)->patch('/tawaran-kerja', ['jawaban' => 'tolak'])->assertSessionHasErrors('alasan');
        $this->actingAs($student)->patch('/tawaran-kerja', ['jawaban' => 'tolak', 'alasan' => 'lainnya'])->assertSessionHasErrors('pesan');
        $this->actingAs($student)->patch('/tawaran-kerja', ['jawaban' => 'tolak', 'alasan' => 'studi', 'pesan' => 'Terima kasih atas kesempatannya.'])->assertSessionHasNoErrors();

        $c = $fajar->fresh()->conversion;
        $this->assertSame('ditolak', $c->status);
        $this->assertSame('Melanjutkan studi', $c->declineReasonLabel());
        $this->actingAs($fajar->supervisor)->get("/pantau/{$fajar->id}")->assertSee('Melanjutkan studi')->assertSee('Terima kasih atas kesempatannya.');
    }

    public function test_tanpa_jawaban_sampai_batas_menjadi_kedaluwarsa_dan_bisa_diperpanjang(): void
    {
        $fajar = $this->recommended();
        $this->actingAs($fajar->supervisor)->post("/pantau/{$fajar->id}/rekrutmen/tawaran", $this->offer(['batas' => now()->addDays(3)->toDateString()]));
        $student = $this->user('fajar@kp.test');

        // Pengingat H-3 ke mahasiswa (sekali)
        app(SendReminders::class)->handle(now());
        app(SendReminders::class)->handle(now());
        $this->assertSame(1, $student->notifications()->where('data', 'like', '%"key":"offer:%')->count());

        // Lewat batas: kedaluwarsa, bukan menolak
        $sent = app(SendReminders::class)->handle(now()->addDays(4));
        $this->assertSame(1, $sent['expired']);
        $c = $fajar->fresh()->conversion;
        $this->assertSame('kedaluwarsa', $c->status);
        $this->actingAs($student)->patch('/tawaran-kerja', ['jawaban' => 'terima'])->assertNotFound();

        // Supervisor memperpanjang: tawaran terbuka lagi
        $this->actingAs($fajar->supervisor)->patch("/pantau/{$fajar->id}/rekrutmen/batas", ['batas' => now()->addDays(5)->toDateString()])->assertSessionHasNoErrors();
        $this->assertSame('ditawari', $c->fresh()->status);
        $this->actingAs($student)->patch('/tawaran-kerja', ['jawaban' => 'terima'])->assertSessionHasNoErrors();
        $this->assertSame('diterima', $c->fresh()->status);
    }

    public function test_hanya_supervisor_penempatan_itu_yang_bisa_mengelola(): void
    {
        $fajar = $this->internshipOf('fajar@kp.test');

        $this->actingAs($fajar->lecturer)->post("/pantau/{$fajar->id}/rekrutmen", ['langkah' => 'rekomendasikan'])->assertForbidden();
        $other = User::where('role', 'supervisor')->where('id', '!=', $fajar->supervisor->id)->first();
        if ($other) {
            $this->actingAs($other)->post("/pantau/{$fajar->id}/rekrutmen", ['langkah' => 'rekomendasikan'])->assertForbidden();
        }

        $fajar = $this->recommended();
        $this->actingAs($fajar->supervisor)->post("/pantau/{$fajar->id}/rekrutmen", ['langkah' => 'batal'])->assertSessionHasNoErrors();
        $this->assertNull($fajar->fresh()->conversion);
        $this->actingAs($this->user('fajar@kp.test'))->patch('/tawaran-kerja', ['jawaban' => 'terima'])->assertNotFound();
    }
}

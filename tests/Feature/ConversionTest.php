<?php

namespace Tests\Feature;

use App\Models\Internship;
use App\Models\User;
use Database\Seeders\TestingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Rekomendasi jadi karyawan: supervisor merekomendasikan dan menawarkan, mahasiswa menjawab. */
class ConversionTest extends TestCase
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

    public function test_supervisor_merekomendasikan_lalu_menawarkan_dan_mahasiswa_menerima(): void
    {
        $fajar = $this->internshipOf('fajar@kp.test');
        $supervisor = $fajar->supervisor;
        $this->assertNotNull($supervisor);

        $this->actingAs($supervisor)->get("/pantau/{$fajar->id}")->assertOk()->assertSee('Rekomendasikan rekrut');
        $this->actingAs($supervisor)->post("/pantau/{$fajar->id}/rekrutmen", ['langkah' => 'rekomendasikan', 'catatan' => 'Cepat belajar'])->assertSessionHasNoErrors();
        $this->assertSame('direkomendasikan', $fajar->fresh()->conversion->status);

        // Mahasiswa belum melihat apa pun sebelum ditawari
        $this->actingAs($this->user('fajar@kp.test'))->get('/dashboard')->assertDontSee('Tawaran kerja dari');

        // Dosen pembimbing melihat kartu, tanpa tombol
        $this->actingAs($fajar->lecturer)->get("/pantau/{$fajar->id}")->assertSee('Rekrutmen')->assertDontSee('Tawarkan kerja');

        $this->actingAs($supervisor)->post("/pantau/{$fajar->id}/rekrutmen", ['langkah' => 'tawarkan'])->assertSessionHasErrors('posisi');
        $this->actingAs($supervisor)->post("/pantau/{$fajar->id}/rekrutmen", ['langkah' => 'tawarkan', 'posisi' => 'Junior Backend Developer'])->assertSessionHasNoErrors();

        $this->actingAs($this->user('fajar@kp.test'))->get('/dashboard')->assertSee('Tawaran kerja dari')->assertSee('Junior Backend Developer');
        $this->actingAs($this->user('fajar@kp.test'))->patch('/tawaran-kerja', ['jawaban' => 'terima'])->assertSessionHasNoErrors();

        $candidate = $fajar->fresh()->conversion;
        $this->assertSame('diterima', $candidate->status);
        $this->assertSame(['direkomendasikan', 'ditawari', 'diterima'], $candidate->histories()->orderBy('id')->pluck('to_status')->all());
        $this->actingAs($this->user('fajar@kp.test'))->get('/dashboard')->assertDontSee('Terima tawaran');
    }

    public function test_hanya_supervisor_penempatan_itu_yang_bisa_mengelola(): void
    {
        $fajar = $this->internshipOf('fajar@kp.test');

        $this->actingAs($fajar->lecturer)->post("/pantau/{$fajar->id}/rekrutmen", ['langkah' => 'rekomendasikan'])->assertForbidden();

        $other = User::where('role', 'supervisor')->where('id', '!=', $fajar->supervisor->id)->first();
        if ($other) {
            $this->actingAs($other)->post("/pantau/{$fajar->id}/rekrutmen", ['langkah' => 'rekomendasikan'])->assertForbidden();
        }

        // Mahasiswa tanpa tawaran tidak bisa menjawab
        $this->actingAs($this->user('fajar@kp.test'))->patch('/tawaran-kerja', ['jawaban' => 'terima'])->assertNotFound();
        $this->assertNull($fajar->fresh()->conversion);
    }

    public function test_supervisor_bisa_membatalkan_rekomendasi_dan_mencatat_penolakan(): void
    {
        $fajar = $this->internshipOf('fajar@kp.test');
        $supervisor = $fajar->supervisor;

        $this->actingAs($supervisor)->post("/pantau/{$fajar->id}/rekrutmen", ['langkah' => 'rekomendasikan']);
        $this->actingAs($supervisor)->post("/pantau/{$fajar->id}/rekrutmen", ['langkah' => 'batal'])->assertSessionHasNoErrors();
        $this->assertNull($fajar->fresh()->conversion);

        $this->actingAs($supervisor)->post("/pantau/{$fajar->id}/rekrutmen", ['langkah' => 'rekomendasikan']);
        $this->actingAs($supervisor)->post("/pantau/{$fajar->id}/rekrutmen", ['langkah' => 'tawarkan', 'posisi' => 'QA']);
        $this->actingAs($supervisor)->post("/pantau/{$fajar->id}/rekrutmen", ['langkah' => 'ditolak'])->assertSessionHasNoErrors();
        $this->assertSame('ditolak', $fajar->fresh()->conversion->status);

        // Sudah final: tidak bisa diubah lagi
        $this->actingAs($supervisor)->post("/pantau/{$fajar->id}/rekrutmen", ['langkah' => 'diterima'])->assertSessionHasErrors('rekrutmen');
    }
}

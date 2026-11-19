<?php

namespace Tests\Feature;

use App\Models\CompanyReview;
use App\Models\Internship;
use App\Models\JobPosting;
use App\Models\User;
use Database\Seeders\TestingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Ulasan tempat magang, berita acara sidang, dan berkas PWA. */
class MediumPriorityTest extends TestCase
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

    private function review(array $override = []): array
    {
        return $override + ['mentoring' => 5, 'work_fit' => 4, 'environment' => 3, 'recommend' => 1, 'comment' => 'Mentor sabar dan tugasnya relevan.'];
    }

    public function test_mahasiswa_selesai_magang_mengulas_tempat_magang(): void
    {
        $salsa = $this->internshipOf('salsa@kp.test');
        $student = $salsa->student;

        $this->actingAs($student)->get('/status-kp')->assertSee('Bagaimana pengalaman magangmu?');
        $this->actingAs($student)->post('/status-kp/ulasan', $this->review(['mentoring' => 6]))->assertSessionHasErrorsIn('ulasan', 'mentoring');
        $this->actingAs($student)->post('/status-kp/ulasan', $this->review())->assertRedirect(route('kp.status').'#ulasan');

        $review = CompanyReview::where('internship_id', $salsa->id)->firstOrFail();
        $this->assertSame('4.00', (string) $review->rating);
        $this->assertSame($salsa->company_id, $review->company_id);

        // Memperbarui, tetap satu ulasan
        $this->actingAs($student)->post('/status-kp/ulasan', $this->review(['environment' => 5]))->assertRedirect();
        $this->assertSame(1, CompanyReview::where('internship_id', $salsa->id)->count());
        $this->assertSame('4.67', (string) $review->fresh()->rating);

        // Tampil tanpa nama di Lowongan dan bernama di halaman perusahaan untuk kaprodi
        JobPosting::where('company_id', $salsa->company_id)->update(['status' => 'published', 'deadline' => today()->addWeek()]);
        if (JobPosting::where('company_id', $salsa->company_id)->exists()) {
            $this->actingAs($this->user('mahasiswa@kp.test'))->get('/lowongan-magang')
                ->assertSee('Kata alumni magang')->assertSee('Mentor sabar dan tugasnya relevan.')->assertDontSee($student->name);
        }
        $this->actingAs($this->user('kaprodi@kp.test'))->get("/perusahaan/{$salsa->company_id}")
            ->assertSee('Ulasan mahasiswa')->assertSee('Mentor sabar dan tugasnya relevan.');
    }

    public function test_magang_berjalan_belum_bisa_mengulas(): void
    {
        $rina = $this->internshipOf('mahasiswa@kp.test');
        $this->assertTrue($rina->end_date->gt(today()), 'Data demo: magang Rina masih berjalan');

        $this->actingAs($rina->student)->get('/status-kp')->assertDontSee('Bagaimana pengalaman magangmu?');
        $this->actingAs($rina->student)->post('/status-kp/ulasan', $this->review())->assertForbidden();
        $this->actingAs($this->user('supervisor@kp.test'))->post('/status-kp/ulasan', $this->review())->assertForbidden();
    }

    public function test_berita_acara_sidang_setelah_disahkan(): void
    {
        $salsa = $this->internshipOf('salsa@kp.test');
        $salsa->defenseScore()->delete();

        $this->actingAs($salsa->student)->get("/sidang/{$salsa->id}/berita-acara")->assertNotFound();

        $salsa->reportScore()->create(['writing' => 27, 'analysis' => 20, 'explaining' => 20, 'conduct' => 15, 'total' => 82, 'graded_by' => $salsa->lecturer_id, 'graded_at' => now()]);
        $this->actingAs($this->user('kaprodi@kp.test'))->put("/kaprodi/sidang/{$salsa->id}", ['defended_at' => today()->toDateString()])->assertRedirect();

        $this->actingAs($salsa->student)->get("/sidang/{$salsa->id}/berita-acara")
            ->assertOk()->assertSee('Berita Acara Sidang Kerja Praktik')->assertSee('Tata penulisan laporan')->assertSee($salsa->student->name);
        $this->actingAs($salsa->lecturer)->get("/sidang/{$salsa->id}/berita-acara")->assertOk();
        $this->actingAs($this->user('kaprodi@kp.test'))->get('/kaprodi/sidang')->assertSee('Berita acara');
        $this->actingAs($this->user('mahasiswa@kp.test'))->get("/sidang/{$salsa->id}/berita-acara")->assertForbidden();
        $this->actingAs($this->user('supervisor@kp.test'))->get("/sidang/{$salsa->id}/berita-acara")->assertForbidden();
    }

    public function test_berkas_pwa_tersedia_dan_ditautkan(): void
    {
        foreach (['manifest.webmanifest', 'sw.js', 'offline.html', 'icons/icon-192.png', 'icons/icon-512.png', 'icons/icon-maskable-512.png', 'icons/apple-touch-icon.png'] as $file) {
            $this->assertFileExists(public_path($file));
        }
        $manifest = json_decode(file_get_contents(public_path('manifest.webmanifest')), true);
        $this->assertSame('SIMAGTI', $manifest['short_name']);
        $this->assertSame('standalone', $manifest['display']);

        $this->get('/')->assertSee('manifest.webmanifest', false);
        $this->actingAs($this->user('mahasiswa@kp.test'))->get('/dashboard')->assertSee('manifest.webmanifest', false)->assertSee('sw.js', false);
    }
}

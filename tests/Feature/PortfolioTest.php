<?php

namespace Tests\Feature;

use App\Models\Internship;
use App\Models\Portfolio;
use App\Models\User;
use Database\Seeders\TestingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortfolioTest extends TestCase
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

    public function test_portofolio_baru_tidak_publik_sampai_mahasiswa_menyalakannya(): void
    {
        $salsa = $this->user('salsa@kp.test');
        $this->actingAs($salsa)->get('/portofolio')->assertOk()->assertSee('Portofolio KP')->assertSee('Tautan publik nonaktif');

        $portfolio = Portfolio::where('internship_id', $this->internshipOf('salsa@kp.test')->id)->firstOrFail();
        $this->assertFalse($portfolio->is_public);

        auth()->logout();
        $this->get("/p/{$portfolio->token}")->assertNotFound();
    }

    public function test_mahasiswa_memilih_isi_dan_halaman_publik_tanpa_nim(): void
    {
        $salsa = $this->user('salsa@kp.test');
        $internship = $this->internshipOf('salsa@kp.test');
        $task = $internship->tasks()->where('status', 'completed')->firstOrFail();
        $other = $this->internshipOf('mahasiswa@kp.test')->tasks()->firstOrFail();

        $this->actingAs($salsa)->patch('/portofolio', [
            'is_public' => '1', 'show_grade' => '0',
            'headline' => 'Data Analyst Intern', 'about' => 'Membangun dashboard penjualan.',
            'skills' => 'Python, SQL, Python, Power BI',
            'task_ids' => [$task->id, $other->id],
        ])->assertRedirect(route('portofolio.edit'))->assertSessionHasNoErrors();

        $portfolio = Portfolio::where('internship_id', $internship->id)->firstOrFail();
        $this->assertTrue($portfolio->is_public);
        $this->assertSame(['Python', 'SQL', 'Power BI'], $portfolio->skills, 'Keahlian dirapikan dan tidak dobel');
        $this->assertSame([$task->id], $portfolio->task_ids, 'Task mahasiswa lain tidak bisa dipilih');

        auth()->logout();
        $page = $this->get("/p/{$portfolio->token}")->assertOk()
            ->assertSee($salsa->name)->assertSee('Data Analyst Intern')->assertSee('Power BI')->assertSee($task->title)
            ->assertSee('Terverifikasi SIMAGTI')
            ->assertHeader('X-Robots-Tag', 'noindex');
        if ($salsa->nim) {
            $page->assertDontSee($salsa->nim);
        }
        $page->assertDontSee('nilai akhir ·', false);
    }

    public function test_nilai_hanya_tampil_bila_diizinkan_dan_tautan_bisa_dimatikan(): void
    {
        $salsa = $this->user('salsa@kp.test');
        $internship = $this->internshipOf('salsa@kp.test');

        $this->actingAs($salsa)->patch('/portofolio', ['is_public' => '1', 'show_grade' => '1'])->assertSessionHasNoErrors();
        $portfolio = Portfolio::where('internship_id', $internship->id)->firstOrFail();

        auth()->logout();
        if ($internship->final_grade) {
            $this->get("/p/{$portfolio->token}")->assertOk()->assertSee('nilai akhir');
        }

        $this->actingAs($salsa)->patch('/portofolio', ['is_public' => '0']);
        auth()->logout();
        $this->get("/p/{$portfolio->token}")->assertNotFound();
    }

    public function test_batas_pilihan_hasil_kerja_dan_hanya_mahasiswa(): void
    {
        $this->actingAs($this->user('salsa@kp.test'))->patch('/portofolio', ['task_ids' => range(1, 7)])
            ->assertSessionHasErrors('task_ids');

        $this->actingAs($this->user('dospem@kp.test'))->get('/portofolio')->assertForbidden();
    }
}

<?php

namespace Tests\Feature;

use App\Models\Internship;
use App\Models\User;
use App\Support\ActivityFeed;
use App\Support\KpProgress;
use Database\Seeders\TestingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Activity monitor untuk supervisor, dosen pembimbing, dan kaprodi; kemajuan KP yang bisa diklik mahasiswa. */
class ActivityMonitorTest extends TestCase
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

    public function test_aktivitas_mahasiswa_tercatat_dan_tampil_untuk_pemantau(): void
    {
        $rina = $this->user('mahasiswa@kp.test');
        $plan = $this->internshipOf('mahasiswa@kp.test')->activityPlans()->first();
        $this->actingAs($rina)->post('/kegiatan/tugas', ['title' => 'Task baru untuk monitor', 'activity_plan_id' => $plan->id]);
        $this->actingAs($rina)->post('/kegiatan/goals', ['title' => 'Goal untuk monitor']);

        foreach (['supervisor@kp.test', 'dospem@kp.test', 'kaprodi@kp.test'] as $email) {
            $this->actingAs($this->user($email))->get('/aktivitas')->assertOk()
                ->assertSee('Task baru untuk monitor')
                ->assertSee('Goal untuk monitor')
                ->assertSee('Rina Putri');
        }

        // Supervisor lain tidak melihat aktivitas Rina
        $this->actingAs($this->user('supervisor2@kp.test'))->get('/aktivitas')->assertOk()->assertDontSee('Task baru untuk monitor');

        // Filter jenis dan mahasiswa
        $this->actingAs($this->user('dospem@kp.test'))->get('/aktivitas?jenis=goal')->assertOk()
            ->assertSee('Goal untuk monitor')->assertDontSee('Task baru untuk monitor');
        $this->get('/aktivitas?mahasiswa='.$this->user('dimas@kp.test')->id)->assertOk()->assertDontSee('Goal untuk monitor');

        // Feed menaut ke task
        $feed = ActivityFeed::for(collect([$this->internshipOf('mahasiswa@kp.test')]), 10);
        $this->assertStringContainsString('/tugas/', $feed->firstWhere('kind', 'task')['url']);
        $this->assertStringContainsString('tab=rencana', $feed->firstWhere('kind', 'goal')['url']);
    }

    public function test_mahasiswa_yang_belum_ada_aktivitas_ditandai(): void
    {
        $this->actingAs($this->user('supervisor@kp.test'))->get('/aktivitas?hari=1')->assertOk()
            ->assertSee('Belum ada aktivitas')->assertSee('Gilang Ramadhan');
    }

    public function test_kemajuan_kp_bisa_diklik_ke_halaman_mahasiswa_dan_tanpa_silabus(): void
    {
        $steps = collect(KpProgress::steps($this->internshipOf('mahasiswa@kp.test')))->keyBy('key');

        $this->assertFalse($steps->has('syllabus'));
        $this->assertSame(route('timesheet.index'), $steps['plans']['url']);
        $this->assertSame(route('kegiatan.index'), $steps['activities']['url']);
        $this->assertSame(route('bimbingan.index'), $steps['guidance']['url']);
        $this->assertFalse($steps->has('documents'), 'Penempatan kampus tidak perlu surat balasan');
        $tari = collect(KpProgress::steps($this->internshipOf('tari@kp.test')))->keyBy('key');
        $this->assertSame(route('dokumen.index'), $tari['documents']['url']);
        $this->assertSame('waiting', $tari['documents']['state']); // sudah diunggah, menunggu diperiksa TU
        $this->assertSame('done', $steps['plans']['state']);

        $this->actingAs($this->user('mahasiswa@kp.test'))->get('/status-kp')->assertOk()
            ->assertSee('class="step-title step-link"', false)
            ->assertSee('Rencana kegiatan')
            ->assertDontSee('Silabus');

        // Gilang belum punya rencana: langkah berikutnya mengarah ke timesheet
        $gilang = collect(KpProgress::steps($this->internshipOf('gilang@kp.test')))->keyBy('key');
        $this->assertSame('current', $gilang['plans']['state']);
        $this->actingAs($this->user('gilang@kp.test'))->get('/status-kp')->assertOk()->assertSee('Buka timesheet');

        // Pengajuan yang belum disetujui: langkah pelaksanaan belum bisa dibuka
        $nadia = collect(KpProgress::steps($this->internshipOf('nadia@kp.test')))->keyBy('key');
        $this->assertNull($nadia['plans']['url']);
        $this->assertSame('upcoming', $nadia['activities']['state']);
    }
}

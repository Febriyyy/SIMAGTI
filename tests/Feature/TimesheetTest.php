<?php

namespace Tests\Feature;

use App\Models\ActivityPlan;
use App\Models\Internship;
use App\Models\TimeEntry;
use App\Models\User;
use Database\Seeders\TestingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Timesheet: rencana kegiatan (Gantt, tanpa jam) diisi lebih dulu, lalu task dibuat di bawah rencana.
 * Catatan kegiatan harian (tanpa durasi jam) + foto opsional, dipantau supervisor dan dospem.
 */
class TimesheetTest extends TestCase
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

    // ---------- Rencana kegiatan (Gantt) ----------

    public function test_timesheet_menampilkan_gantt_rencana_kegiatan(): void
    {
        $rina = $this->internshipOf('mahasiswa@kp.test');
        $this->assertGreaterThanOrEqual(3, $rina->activityPlans()->count());

        $this->actingAs($this->user('mahasiswa@kp.test'))->get('/timesheet')->assertOk()
            ->assertSee('Rencana kegiatan')
            ->assertSee('class="gt" data-gantt', false)
            ->assertSee('Orientasi dan pengenalan sistem')
            ->assertSee('Modul Pemesanan')
            ->assertSee('Penyusunan laporan akhir')
            ->assertSee('Rencana baru')
            ->assertDontSee('Durasi (jam)')
            ->assertDontSee(' jam<', false);
    }

    public function test_gantt_menampilkan_task_di_bawah_rencana_dan_warna_bisa_dipilih(): void
    {
        $rina = $this->internshipOf('mahasiswa@kp.test');
        $plan = $rina->activityPlans()->whereHas('tasks')->with('tasks')->firstOrFail();
        $task = $plan->tasks->first();
        $student = $this->user('mahasiswa@kp.test');

        $this->actingAs($student)->get('/timesheet')->assertOk()
            ->assertSee('data-gt-toggle="'.$plan->id.'"', false)
            ->assertSee('data-gt-parent="'.$plan->id.'"', false)
            ->assertSee($task->title)
            ->assertSee('name="color"', false);

        $this->patch("/timesheet/rencana/{$plan->id}", ['title' => $plan->title, 'start_date' => $plan->start_date->toDateString(), 'end_date' => $plan->end_date->toDateString(), 'color' => 'purple'])
            ->assertSessionHasNoErrors();
        $this->assertSame('purple', $plan->fresh()->color);
        $this->get('/timesheet')->assertSee('--c: var(--gc-purple)', false);

        $this->patch("/timesheet/rencana/{$plan->id}", ['title' => $plan->title, 'start_date' => $plan->start_date->toDateString(), 'end_date' => $plan->end_date->toDateString(), 'color' => 'hitam'])
            ->assertSessionHasErrors('color');
    }

    public function test_mahasiswa_menyusun_rencana_dalam_periode_kp(): void
    {
        $gilang = $this->internshipOf('gilang@kp.test'); // belum punya rencana
        $student = $this->user('gilang@kp.test');

        $this->actingAs($student)->get('/timesheet')->assertOk()->assertSee('Belum ada rencana');
        $this->actingAs($student)->get('/kegiatan')->assertOk()->assertSee('Isi rencana kegiatan dulu')->assertDontSee('data-open="dlg-task"', false);

        // Tanggal harus di dalam periode KP dan selesai tidak sebelum mulai
        $this->post('/timesheet/rencana', ['title' => 'Terlalu awal', 'start_date' => $gilang->start_date->copy()->subDay()->toDateString(), 'end_date' => $gilang->start_date->copy()->addDays(5)->toDateString()])
            ->assertSessionHasErrors('start_date');
        $this->post('/timesheet/rencana', ['title' => 'Terbalik', 'start_date' => $gilang->start_date->copy()->addDays(9)->toDateString(), 'end_date' => $gilang->start_date->copy()->addDays(2)->toDateString()])
            ->assertSessionHasErrors('end_date');
        $this->post('/timesheet/rencana', ['title' => 'Terlalu lama', 'start_date' => $gilang->start_date->toDateString(), 'end_date' => $gilang->end_date->copy()->addDay()->toDateString()])
            ->assertSessionHasErrors('end_date');

        $this->post('/timesheet/rencana', [
            'title' => 'Menyusun skenario pengujian', 'start_date' => $gilang->start_date->toDateString(),
            'end_date' => $gilang->start_date->copy()->addDays(13)->toDateString(), 'description' => 'Test case modul login',
        ])->assertSessionHasNoErrors();

        $plan = ActivityPlan::where('internship_id', $gilang->id)->firstOrFail();
        $this->assertSame(14, $plan->days());
        $this->get('/timesheet')->assertOk()->assertSee('Menyusun skenario pengujian');

        // Setelah ada rencana, task bisa dibuat di bawahnya
        $this->get('/kegiatan')->assertOk()->assertSee('data-open="dlg-task"', false)->assertSee('Menyusun skenario pengujian');
        $this->post('/kegiatan/tugas', ['title' => 'Tulis test case login', 'activity_plan_id' => $plan->id])->assertSessionHasNoErrors();
        $this->assertSame(1, $plan->tasks()->count());

        // Rencana yang punya task tidak bisa dihapus
        $this->delete("/timesheet/rencana/{$plan->id}")->assertSessionHasErrors('plan');
        $this->patch("/timesheet/rencana/{$plan->id}", ['title' => 'Skenario pengujian modul login', 'start_date' => $plan->start_date->toDateString(), 'end_date' => $plan->end_date->toDateString()])
            ->assertSessionHasNoErrors();
        $this->assertSame('Skenario pengujian modul login', $plan->fresh()->title);
    }

    public function test_rencana_hanya_untuk_pemilik(): void
    {
        $plan = $this->internshipOf('mahasiswa@kp.test')->activityPlans()->firstOrFail();

        $this->actingAs($this->user('dimas@kp.test'))->delete("/timesheet/rencana/{$plan->id}")->assertNotFound();
        $this->actingAs($this->user('supervisor@kp.test'))->post('/timesheet/rencana', ['title' => 'x'])->assertForbidden();
        $this->actingAs($this->user('salsa@kp.test'))->post('/timesheet/rencana', ['title' => 'x', 'start_date' => now()->toDateString(), 'end_date' => now()->toDateString()])
            ->assertForbidden(); // KP selesai terkunci
    }

    // ---------- Catatan kegiatan harian ----------

    public function test_catatan_kegiatan_tanpa_durasi_jam(): void
    {
        $this->assertFalse(Schema::hasColumn('time_entries', 'hours'));
        $this->assertFalse(Schema::hasColumn('weekly_tasks', 'estimate_hours'));
    }

    public function test_mahasiswa_mencatat_kegiatan_dengan_foto_dan_task(): void
    {
        $rina = $this->internshipOf('mahasiswa@kp.test');
        $task = $rina->tasks()->where('status', '!=', 'completed')->firstOrFail();
        $before = $rina->timeEntries()->count();

        $this->actingAs($this->user('mahasiswa@kp.test'))->post('/timesheet', [
            'work_date' => now()->toDateString(),
            'description' => 'Rapat sprint planning',
            'weekly_task_id' => $task->id,
            'foto' => UploadedFile::fake()->image('dokumentasi.jpg', 400, 300),
        ])->assertRedirect()->assertSessionHasNoErrors();

        $entry = TimeEntry::where('description', 'Rapat sprint planning')->firstOrFail();
        $this->assertSame($task->id, $entry->weekly_task_id);
        $this->assertSame($before + 1, $rina->timeEntries()->count());
        $this->assertNotNull($entry->photo);
        Storage::disk('local')->assertExists($entry->photo->path);
        $this->assertStringStartsWith("timesheet/{$rina->id}/", $entry->photo->path);

        $this->get('/timesheet')->assertOk()->assertSee('Rapat sprint planning')->assertSee('Catatan kegiatan');
    }

    public function test_aturan_tanggal_foto_dan_task(): void
    {
        $rina = $this->internshipOf('mahasiswa@kp.test');
        $student = $this->user('mahasiswa@kp.test');
        $base = ['description' => 'x'];

        $this->actingAs($student)->post('/timesheet', $base + ['work_date' => now()->addDay()->toDateString()])->assertSessionHasErrors('work_date');
        $this->post('/timesheet', $base + ['work_date' => $rina->start_date->copy()->subDay()->toDateString()])->assertSessionHasErrors('work_date');
        $this->post('/timesheet', ['work_date' => now()->toDateString()])->assertSessionHasErrors('description');

        // Foto hanya gambar
        $this->post('/timesheet', $base + ['work_date' => now()->toDateString(), 'foto' => UploadedFile::fake()->create('virus.php', 10, 'text/x-php')])->assertSessionHasErrors('foto');

        // Task harus milik sendiri
        $this->post('/timesheet', $base + ['work_date' => now()->toDateString(), 'weekly_task_id' => $this->internshipOf('dimas@kp.test')->tasks()->first()->id])
            ->assertSessionHasErrors('weekly_task_id');
    }

    public function test_hanya_pemilik_yang_mengubah_dan_menghapus_catatan(): void
    {
        $entry = $this->internshipOf('mahasiswa@kp.test')->timeEntries()->firstOrFail();

        $this->actingAs($this->user('dimas@kp.test'))->patch("/timesheet/{$entry->id}", ['description' => 'Ubah'])->assertForbidden();
        $this->actingAs($this->user('dimas@kp.test'))->delete("/timesheet/{$entry->id}")->assertForbidden();
        $this->actingAs($this->user('supervisor@kp.test'))->delete("/timesheet/{$entry->id}")->assertForbidden();

        $this->actingAs($this->user('mahasiswa@kp.test'))->patch("/timesheet/{$entry->id}", ['description' => 'Catatan diperbaiki'])->assertSessionHasNoErrors();
        $this->assertSame('Catatan diperbaiki', $entry->fresh()->description);

        $this->delete("/timesheet/{$entry->id}")->assertRedirect();
        $this->assertDatabaseMissing('time_entries', ['id' => $entry->id]);
    }

    public function test_timesheet_kp_selesai_terkunci(): void
    {
        $salsa = $this->internshipOf('salsa@kp.test');
        $entry = $salsa->timeEntries()->firstOrFail();

        $this->actingAs($this->user('salsa@kp.test'))->get('/timesheet')->assertOk()->assertDontSee('Catat kegiatan')->assertDontSee('Rencana baru');
        $this->post('/timesheet', ['work_date' => $salsa->end_date->toDateString(), 'description' => 'x'])->assertForbidden();
        $this->delete("/timesheet/{$entry->id}")->assertForbidden();
    }

    public function test_foto_dokumentasi_hanya_untuk_yang_berhak(): void
    {
        $this->actingAs($this->user('mahasiswa@kp.test'))->post('/timesheet', [
            'work_date' => now()->toDateString(), 'description' => 'Foto uji',
            'foto' => UploadedFile::fake()->image('a.png'),
        ]);
        $entry = TimeEntry::where('description', 'Foto uji')->firstOrFail();

        $this->get("/timesheet/{$entry->id}/foto")->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->actingAs($this->user('supervisor@kp.test'))->get("/timesheet/{$entry->id}/foto")->assertOk();
        $this->actingAs($this->user('dospem@kp.test'))->get("/timesheet/{$entry->id}/foto")->assertOk();
        $this->actingAs($this->user('dimas@kp.test'))->get("/timesheet/{$entry->id}/foto")->assertForbidden();
        $this->actingAs($this->user('supervisor2@kp.test'))->get("/timesheet/{$entry->id}/foto")->assertForbidden();
        $this->actingAs($this->user('tu@kp.test'))->get("/timesheet/{$entry->id}/foto")->assertForbidden();
    }

    public function test_navigasi_periode(): void
    {
        $this->actingAs($this->user('mahasiswa@kp.test'))->get('/timesheet?p=-1')->assertOk()->assertSee('Minggu ke-');
        $this->get('/timesheet?periode=semua')->assertOk()->assertSee('Seluruh periode KP');
        $this->actingAs($this->user('dimas@kp.test'))->get('/timesheet')->assertOk()->assertDontSee('MBKM');
    }
}

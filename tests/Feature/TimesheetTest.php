<?php

namespace Tests\Feature;

use App\Models\Internship;
use App\Models\TimeEntry;
use App\Models\User;
use Database\Seeders\TestingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Timesheet harian: jam kerja + foto opsional, dicatat mahasiswa, dipantau supervisor dan dospem. */
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

    public function test_mahasiswa_mencatat_kegiatan_dengan_foto_dan_task(): void
    {
        $rina = $this->internshipOf('mahasiswa@kp.test');
        $task = $rina->tasks()->where('status', '!=', 'completed')->firstOrFail();
        $before = (float) $rina->timeEntries()->sum('hours');

        $this->actingAs($this->user('mahasiswa@kp.test'))->post('/timesheet', [
            'work_date' => now()->toDateString(),
            'hours' => 1.5,
            'description' => 'Rapat sprint planning',
            'weekly_task_id' => $task->id,
            'foto' => UploadedFile::fake()->image('dokumentasi.jpg', 400, 300),
        ])->assertRedirect()->assertSessionHasNoErrors();

        $entry = TimeEntry::where('description', 'Rapat sprint planning')->firstOrFail();
        $this->assertSame($task->id, $entry->weekly_task_id);
        $this->assertEquals($before + 1.5, (float) $rina->timeEntries()->sum('hours'));
        $this->assertNotNull($entry->photo);
        Storage::disk('local')->assertExists($entry->photo->path);
        $this->assertStringStartsWith("timesheet/{$rina->id}/", $entry->photo->path);

        $this->get('/timesheet')->assertOk()->assertSee('Rapat sprint planning');
    }

    public function test_aturan_tanggal_dan_jam(): void
    {
        $rina = $this->internshipOf('mahasiswa@kp.test');
        $student = $this->user('mahasiswa@kp.test');
        $base = ['hours' => 2, 'description' => 'x'];

        $this->actingAs($student)->post('/timesheet', $base + ['work_date' => now()->addDay()->toDateString()])->assertSessionHasErrors('work_date');
        $this->post('/timesheet', $base + ['work_date' => $rina->start_date->copy()->subDay()->toDateString()])->assertSessionHasErrors('work_date');
        $this->post('/timesheet', ['work_date' => now()->toDateString(), 'hours' => 17, 'description' => 'x'])->assertSessionHasErrors('hours');
        $this->post('/timesheet', ['work_date' => now()->toDateString(), 'hours' => 0, 'description' => 'x'])->assertSessionHasErrors('hours');

        // Total satu hari maksimal 24 jam
        $day = $rina->start_date->copy()->addDays(2)->toDateString();
        $logged = (float) $rina->timeEntries()->whereDate('work_date', $day)->sum('hours');
        $this->post('/timesheet', ['work_date' => $day, 'hours' => 16, 'description' => 'lembur'])->assertSessionHasNoErrors();
        $this->post('/timesheet', ['work_date' => $day, 'hours' => 24 - 16 - $logged + 0.25, 'description' => 'lembur lagi'])->assertSessionHasErrors('hours');

        // Foto hanya gambar
        $this->post('/timesheet', $base + ['work_date' => now()->toDateString(), 'foto' => UploadedFile::fake()->create('virus.php', 10, 'text/x-php')])->assertSessionHasErrors('foto');

        // Task harus milik sendiri
        $this->post('/timesheet', $base + ['work_date' => now()->toDateString(), 'weekly_task_id' => $this->internshipOf('dimas@kp.test')->tasks()->first()->id])
            ->assertSessionHasErrors('weekly_task_id');
    }

    public function test_hanya_pemilik_yang_mengubah_dan_menghapus_catatan(): void
    {
        $entry = $this->internshipOf('mahasiswa@kp.test')->timeEntries()->firstOrFail();

        $this->actingAs($this->user('dimas@kp.test'))->patch("/timesheet/{$entry->id}", ['hours' => 1])->assertForbidden();
        $this->actingAs($this->user('dimas@kp.test'))->delete("/timesheet/{$entry->id}")->assertForbidden();
        $this->actingAs($this->user('supervisor@kp.test'))->delete("/timesheet/{$entry->id}")->assertForbidden();

        $this->actingAs($this->user('mahasiswa@kp.test'))->patch("/timesheet/{$entry->id}", ['hours' => 3.25])->assertSessionHasNoErrors();
        $this->assertEquals(3.25, (float) $entry->fresh()->hours);

        $this->delete("/timesheet/{$entry->id}")->assertRedirect();
        $this->assertDatabaseMissing('time_entries', ['id' => $entry->id]);
    }

    public function test_timesheet_kp_selesai_terkunci(): void
    {
        $salsa = $this->internshipOf('salsa@kp.test');
        $entry = $salsa->timeEntries()->firstOrFail();

        $this->actingAs($this->user('salsa@kp.test'))->get('/timesheet')->assertOk()->assertDontSee('Catat kegiatan');
        $this->post('/timesheet', ['work_date' => $salsa->end_date->toDateString(), 'hours' => 1, 'description' => 'x'])->assertForbidden();
        $this->delete("/timesheet/{$entry->id}")->assertForbidden();
    }

    public function test_foto_dokumentasi_hanya_untuk_yang_berhak(): void
    {
        $this->actingAs($this->user('mahasiswa@kp.test'))->post('/timesheet', [
            'work_date' => now()->toDateString(), 'hours' => 1, 'description' => 'Foto uji',
            'foto' => UploadedFile::fake()->image('a.png'),
        ]);
        $entry = TimeEntry::where('description', 'Foto uji')->firstOrFail();

        $this->get("/timesheet/{$entry->id}/foto")->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->actingAs($this->user('supervisor@kp.test'))->get("/timesheet/{$entry->id}/foto")->assertOk();
        $this->actingAs($this->user('dospem@kp.test'))->get("/timesheet/{$entry->id}/foto")->assertOk();
        $this->actingAs($this->user('dimas@kp.test'))->get("/timesheet/{$entry->id}/foto")->assertForbidden();
        $this->actingAs($this->user('supervisor2@kp.test'))->get("/timesheet/{$entry->id}/foto")->assertForbidden();
        $this->actingAs($this->user('biro@kp.test'))->get("/timesheet/{$entry->id}/foto")->assertForbidden();
    }

    public function test_navigasi_periode_dan_target_mbkm(): void
    {
        $this->actingAs($this->user('mahasiswa@kp.test'))->get('/timesheet?p=-1')->assertOk()->assertSee('Minggu ke-');
        $this->get('/timesheet?periode=semua')->assertOk()->assertSee('Seluruh periode KP');

        $this->actingAs($this->user('dimas@kp.test'))->get('/timesheet')->assertOk()->assertSee('Target logbook MBKM')->assertSee('450');
    }
}

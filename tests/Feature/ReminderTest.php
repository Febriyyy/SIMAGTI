<?php

namespace Tests\Feature;

use App\Actions\Reminders\SendReminders;
use App\Models\GuidanceSession;
use App\Models\Internship;
use App\Models\User;
use App\Notifications\KpReminder;
use Database\Seeders\TestingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ReminderTest extends TestCase
{
    use RefreshDatabase;

    private Carbon $monday;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TestingSeeder::class);
        // Hari kerja tetap agar pengingat catatan (tidak dikirim akhir pekan) bisa diuji kapan pun.
        $this->monday = Carbon::today()->isMonday() ? Carbon::today() : Carbon::today()->next(Carbon::MONDAY);
        $this->travelTo($this->monday->copy()->setTime(7, 0));
    }

    private function user(string $email): User
    {
        return User::where('email', $email)->firstOrFail();
    }

    private function running(): Internship
    {
        $internship = Internship::where('student_id', $this->user('mahasiswa@kp.test')->id)->latest('id')->firstOrFail();
        $internship->update(['status' => 'berjalan', 'start_date' => $this->monday->copy()->subDays(30), 'end_date' => $this->monday->copy()->addDays(60)]);
        $internship->assessment()->delete();

        return $internship->fresh();
    }

    private function reminders(User $user, string $keyPrefix): int
    {
        return $user->fresh()->notifications()->where('data', 'like', '%"key":"'.$keyPrefix.'%')->count();
    }

    public function test_mahasiswa_diingatkan_bila_tiga_hari_tidak_mengisi_catatan_dan_tidak_dobel(): void
    {
        $internship = $this->running();
        $internship->timeEntries()->delete();
        $student = $this->user('mahasiswa@kp.test');

        app(SendReminders::class)->handle($this->monday);
        $this->assertSame(1, $this->reminders($student, "idle:{$internship->id}"));

        app(SendReminders::class)->handle($this->monday);
        $this->assertSame(1, $this->reminders($student, "idle:{$internship->id}"), 'Hari yang sama tidak dikirim ulang');

        // Baru mengisi catatan kemarin: tidak diingatkan
        $student->notifications()->delete();
        $internship->timeEntries()->create(['work_date' => $this->monday->copy()->subDay(), 'description' => 'Mengerjakan modul', 'created_by' => $student->id]);
        app(SendReminders::class)->handle($this->monday);
        $this->assertSame(0, $this->reminders($student, 'idle:'));
    }

    public function test_task_mendekati_tenggat_diingatkan_sekali(): void
    {
        $internship = $this->running();
        $task = $internship->tasks()->where('status', '!=', 'completed')->firstOrFail();
        $task->update(['due_date' => $this->monday->copy()->addDay()]);
        $student = $this->user('mahasiswa@kp.test');

        app(SendReminders::class)->handle($this->monday);
        app(SendReminders::class)->handle($this->monday);

        $this->assertSame(1, $this->reminders($student, "due:{$task->id}:"));
        $this->assertStringContainsString('Tenggat task', $student->notifications()->where('data', 'like', '%"key":"due:'.$task->id.'%')->first()->data['title']);
    }

    public function test_supervisor_diingatkan_saat_penilaian_dibuka_dan_menjelang_akhir(): void
    {
        $internship = $this->running();
        $supervisor = $internship->supervisor;
        $this->assertNotNull($supervisor);

        $internship->update(['end_date' => $this->monday->copy()->addDays(10)]);
        app(SendReminders::class)->handle($this->monday);
        $this->assertSame(1, $this->reminders($supervisor, "assess-open:{$internship->id}"));

        $internship->update(['end_date' => $this->monday->copy()->addDays(2)]);
        app(SendReminders::class)->handle($this->monday);
        $this->assertSame(1, $this->reminders($supervisor, "assess-due:{$internship->id}"));

        // Penilaian belum dibuka (masih jauh dari akhir KP): tidak ada pengingat baru
        $supervisor->notifications()->delete();
        $internship->update(['end_date' => $this->monday->copy()->addDays(40)]);
        app(SendReminders::class)->handle($this->monday);
        $this->assertSame(0, $this->reminders($supervisor, 'assess-'));
    }

    public function test_dosen_diingatkan_bimbingan_yang_lama_menunggu(): void
    {
        $internship = $this->running();
        $lecturer = $internship->lecturer;
        $this->assertNotNull($lecturer);

        $this->travelTo($this->monday->copy()->subDays(5));
        GuidanceSession::create(['internship_id' => $internship->id, 'held_on' => $this->monday->copy()->subDays(5), 'mode' => 'daring', 'topic' => 'Progres bab 1', 'status' => 'pending', 'created_by' => $internship->student_id]);
        $this->travelTo($this->monday->copy()->setTime(7, 0));

        app(SendReminders::class)->handle($this->monday);
        $this->assertSame(1, $this->reminders($lecturer, "guidance:{$lecturer->id}"));
    }

    public function test_pengingat_juga_dikirim_lewat_email_dan_perintah_terjadwal(): void
    {
        Notification::fake();
        $internship = $this->running();
        $internship->timeEntries()->delete();

        $this->artisan('kp:pengingat')->assertSuccessful();

        Notification::assertSentTo($this->user('mahasiswa@kp.test'), KpReminder::class, fn (KpReminder $n, array $channels) => in_array('mail', $channels, true) && in_array('database', $channels, true));
    }
}

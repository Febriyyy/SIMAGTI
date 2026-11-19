<?php

namespace Tests\Feature;

use App\Actions\Reminders\SendReminders;
use App\Actions\Reports\ManageReportChapters;
use App\Models\GuidanceSession;
use App\Models\Internship;
use App\Models\User;
use App\Notifications\KpEmail;
use App\Support\CalendarLink;
use Database\Seeders\TestingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Riwayat versi bab, email kabar penting, pengingat jadwal bimbingan dan sidang, tautan Google Calendar. */
class ScheduleReminderTest extends TestCase
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

    public function test_unggah_ulang_bab_menyimpan_versi_lama(): void
    {
        $rina = $this->internshipOf('mahasiswa@kp.test');
        $chapter = ManageReportChapters::ensureFor($rina)->firstWhere('needs_supervisor', false);
        $student = $rina->student;

        $this->actingAs($student)->post("/dokumen/laporan/{$chapter->id}", ['berkas' => UploadedFile::fake()->create('bab1-v1.pdf', 50, 'application/pdf')]);
        $this->actingAs($student)->post("/dokumen/laporan/{$chapter->id}", ['berkas' => UploadedFile::fake()->create('bab1-v2.pdf', 50, 'application/pdf')]);

        $chapter->refresh();
        $this->assertSame(2, $chapter->version);
        $this->assertSame([2, 1], $chapter->versions()->pluck('version')->all());
        $old = $chapter->versions()->where('version', 1)->firstOrFail();
        $this->assertTrue(Storage::disk('local')->exists($old->file->path), 'Berkas versi lama tetap ada');

        $this->actingAs($student)->get("/laporan-bab/versi/{$old->id}/berkas")->assertOk();
        $this->actingAs($rina->lecturer)->get("/laporan-kp/{$rina->id}")->assertSee('Versi sebelumnya')->assertSee('v1');
        $this->actingAs($this->user('salsa@kp.test'))->get("/laporan-bab/versi/{$old->id}/berkas")->assertForbidden();
    }

    public function test_kabar_penting_juga_dikirim_lewat_email(): void
    {
        Notification::fake();
        $rina = $this->internshipOf('mahasiswa@kp.test');

        $this->actingAs($rina->lecturer)->post('/antrean-bimbingan/jadwal', [
            'internship_id' => $rina->id, 'held_on' => today()->addDay()->toDateString(), 'start_time' => '10:00',
            'mode' => 'daring', 'topic' => 'Revisi bab 3', 'meeting_link' => 'https://meet.google.com/abc-defg-hij',
        ])->assertSessionHasNoErrors();

        Notification::assertSentTo($rina->student, KpEmail::class, fn (KpEmail $n) => str_contains($n->title, 'Jadwal bimbingan'));
        // Kabar biasa (bukan penting) tidak dikirim lewat email
        Notification::assertNotSentTo($rina->lecturer, KpEmail::class);
    }

    public function test_pengingat_bimbingan_sehari_sebelum_dan_menjelang_mulai_tanpa_dobel(): void
    {
        $rina = $this->internshipOf('mahasiswa@kp.test');
        $start = Carbon::parse('2026-11-20 10:00');
        $session = GuidanceSession::create([
            'internship_id' => $rina->id, 'held_on' => $start->toDateString(), 'start_time' => '10:00', 'mode' => 'daring',
            'meeting_link' => 'https://meet.google.com/abc', 'topic' => 'Revisi bab 3', 'status' => 'scheduled', 'created_by' => $rina->lecturer_id,
        ]);
        $reminders = app(SendReminders::class);
        $count = fn (string $text) => $rina->student->notifications()->where('data', 'like', '%'.$text.'%')->count();

        $this->assertSame(0, $reminders->upcoming($start->copy()->subHours(30))['guidance'], 'Masih lebih dari sehari');
        $this->assertSame(1, $reminders->upcoming($start->copy()->subHours(20))['guidance']);
        $this->assertSame(0, $reminders->upcoming($start->copy()->subHours(19))['guidance'], 'Tidak dobel');
        $this->assertSame(1, $count('Pengingat bimbingan'));

        $this->assertSame(1, $reminders->upcoming($start->copy()->subMinutes(60))['guidance']);
        $this->assertSame(0, $reminders->upcoming($start->copy()->subMinutes(45))['guidance']);
        $this->assertSame(1, $count('Sebentar lagi'));
        $this->assertSame(1, $rina->lecturer->notifications()->where('data', 'like', '%Sebentar lagi%')->count());

        $this->assertSame(0, $reminders->upcoming($start->copy()->addMinutes(5))['guidance'], 'Sudah lewat');

        // Batal: tidak diingatkan
        $session->update(['status' => 'rejected', 'held_on' => '2026-11-22']);
        $this->assertSame(0, $reminders->upcoming(Carbon::parse('2026-11-21 12:00'))['guidance']);
    }

    public function test_pengingat_sidang_untuk_mahasiswa_dosen_dan_penguji(): void
    {
        $salsa = $this->internshipOf('salsa@kp.test');
        $salsa->defenseScore()->delete();
        $start = now()->addHours(10)->startOfHour();
        $schedule = $salsa->defenseSchedule()->create(['scheduled_at' => $start, 'mode' => 'luring', 'place' => 'B2.04', 'created_by' => $this->user('kaprodi@kp.test')->id]);

        $this->assertSame(1, app(SendReminders::class)->upcoming()['defense']);
        $this->assertSame(1, $salsa->student->notifications()->where('data', 'like', '%Pengingat: sidang KP%')->count());
        $this->assertSame(1, $salsa->lecturer->notifications()->where('data', 'like', '%sidang KP%')->count());

        $this->artisan('kp:pengingat-jadwal')->assertSuccessful();
        $this->assertSame(1, $salsa->student->notifications()->where('data', 'like', '%Pengingat: sidang KP%')->count());

        // Tautan Google Calendar pada Status KP mahasiswa
        $this->actingAs($salsa->student)->get('/status-kp')->assertSee('Tambah ke Google Calendar');
        $this->assertStringContainsString('calendar.google.com/calendar/render?action=TEMPLATE', CalendarLink::defense($schedule, 'Salsa'));
    }

    public function test_tautan_google_calendar_bimbingan_berisi_waktu_utc(): void
    {
        $rina = $this->internshipOf('mahasiswa@kp.test');
        $session = GuidanceSession::create([
            'internship_id' => $rina->id, 'held_on' => today()->addDay()->toDateString(), 'start_time' => '10:00', 'mode' => 'daring',
            'meeting_link' => 'https://meet.google.com/abc', 'topic' => 'Revisi bab 3', 'status' => 'scheduled', 'created_by' => $rina->lecturer_id,
        ]);

        $url = CalendarLink::guidance($session);
        // 10.00 WIB = 03.00 UTC, durasi 60 menit
        $this->assertStringContainsString('dates='.today()->addDay()->format('Ymd').'T030000Z%2F'.today()->addDay()->format('Ymd').'T040000Z', $url);
        $this->actingAs($rina->student)->get('/bimbingan')->assertSee('Tambah ke Google Calendar');
    }
}

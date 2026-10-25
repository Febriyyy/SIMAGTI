<?php

namespace Tests\Feature;

use App\Models\Internship;
use App\Models\User;
use App\Models\WeeklyTask;
use Database\Seeders\TestingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Masuk, akses per peran, bukti task, komentar, dan meeting (KP v2). */
class KpFlowTest extends TestCase
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

    private function taskOf(string $email): WeeklyTask
    {
        return WeeklyTask::whereHas('internship', fn ($q) => $q->where('student_id', $this->user($email)->id))->orderBy('id')->firstOrFail();
    }

    public function test_tamu_diarahkan_ke_halaman_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/kegiatan')->assertRedirect('/login');
    }

    public function test_setiap_peran_bisa_masuk_dan_melihat_dashboard(): void
    {
        $emails = ['mahasiswa@kp.test', 'supervisor@kp.test', 'dospem@kp.test', 'kaprodi@kp.test', 'biro@kp.test', 'tu@kp.test',
                   'gilang@kp.test', 'nadia@kp.test', 'citra@kp.test', 'salsa@kp.test'];

        foreach ($emails as $email) {
            $this->post('/login', ['email' => $email, 'password' => 'password'])->assertRedirect('/dashboard');
            $this->get('/dashboard')->assertOk();
            $this->post('/logout');
        }
    }

    public function test_menu_mengikuti_peran(): void
    {
        $this->actingAs($this->user('mahasiswa@kp.test'))->get('/dashboard')
            ->assertSee('Status KP')->assertSee('Silabus')->assertSee('Kegiatan')->assertSee('Timesheet');

        $this->actingAs($this->user('supervisor@kp.test'))->get('/dashboard')
            ->assertSee('Mahasiswa Magang')->assertSee('Penilaian Akhir')->assertDontSee('Timesheet');

        $this->actingAs($this->user('tu@kp.test'))->get('/dashboard')->assertSee('Aktivasi Supervisor');
    }

    public function test_akun_nonaktif_tidak_bisa_masuk(): void
    {
        $this->user('mahasiswa@kp.test')->update(['is_active' => false]);

        $this->post('/login', ['email' => 'mahasiswa@kp.test', 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_halaman_per_peran_tertutup_untuk_peran_lain(): void
    {
        $rina = $this->user('mahasiswa@kp.test');
        $this->actingAs($rina)->get('/pantau')->assertForbidden();
        $this->actingAs($rina)->get('/penilaian')->assertForbidden();
        $this->actingAs($rina)->get('/tu/aktivasi')->assertForbidden();

        $supervisor = $this->user('supervisor@kp.test');
        $this->actingAs($supervisor)->get('/kegiatan')->assertForbidden();
        $this->actingAs($supervisor)->get('/timesheet')->assertForbidden();
        $this->actingAs($supervisor)->get('/silabus')->assertForbidden();
        $this->actingAs($supervisor)->post('/kegiatan/tugas', ['title' => 'Tidak boleh'])->assertForbidden();
    }

    public function test_hanya_kaprodi_yang_melihat_log_aktivitas(): void
    {
        $this->actingAs($this->user('supervisor@kp.test'))->get('/log-aktivitas')->assertForbidden();
        $this->actingAs($this->user('kaprodi@kp.test'))->get('/log-aktivitas')->assertOk();
    }

    public function test_mahasiswa_bisa_mengirim_bukti_untuk_task_sendiri(): void
    {
        $rina = $this->user('mahasiswa@kp.test');
        $task = $this->taskOf('mahasiswa@kp.test');

        $this->actingAs($rina)
            ->patch("/tugas/{$task->id}/bukti", ['evidence_url' => 'https://github.com/contoh/proyek/pull/99'])
            ->assertRedirect();

        $this->assertDatabaseHas('task_evidences', ['weekly_task_id' => $task->id, 'submitted_by' => $rina->id, 'url' => 'https://github.com/contoh/proyek/pull/99']);
    }

    public function test_mahasiswa_tidak_bisa_mengirim_bukti_untuk_task_orang_lain(): void
    {
        $this->actingAs($this->user('mahasiswa@kp.test'))
            ->patch('/tugas/'.$this->taskOf('dimas@kp.test')->id.'/bukti', ['evidence_url' => 'https://example.com'])
            ->assertForbidden();
    }

    public function test_link_bukti_harus_http_atau_https(): void
    {
        $this->actingAs($this->user('mahasiswa@kp.test'))
            ->patch('/tugas/'.$this->taskOf('mahasiswa@kp.test')->id.'/bukti', ['evidence_url' => 'javascript:alert(1)'])
            ->assertSessionHasErrors('evidence_url');
    }

    public function test_detail_task_hanya_untuk_pemilik_supervisor_dospem_dan_kaprodi(): void
    {
        $task = $this->taskOf('mahasiswa@kp.test');

        $this->actingAs($this->user('mahasiswa@kp.test'))->get("/tugas/{$task->id}")->assertOk()->assertSee($task->key());
        $this->actingAs($this->user('supervisor@kp.test'))->get("/tugas/{$task->id}")->assertOk()->assertDontSee('data-open="dlg-edit"', false);
        $this->actingAs($this->user('dospem@kp.test'))->get("/tugas/{$task->id}")->assertOk();
        $this->actingAs($this->user('kaprodi@kp.test'))->get("/tugas/{$task->id}")->assertOk();
        $this->actingAs($this->user('dimas@kp.test'))->get("/tugas/{$task->id}")->assertForbidden();
        $this->actingAs($this->user('supervisor2@kp.test'))->get("/tugas/{$task->id}")->assertForbidden();
        $this->actingAs($this->user('tu@kp.test'))->get("/tugas/{$task->id}")->assertForbidden();
    }

    public function test_supervisor_memberi_komentar_tapi_tidak_bisa_mengubah_task(): void
    {
        $task = $this->taskOf('mahasiswa@kp.test');
        $supervisor = $this->user('supervisor@kp.test');

        $this->actingAs($supervisor)->post("/tugas/{$task->id}/komentar", ['body' => 'Tambahkan uji untuk kasus kosong.'])->assertRedirect();
        $this->assertDatabaseHas('task_comments', ['weekly_task_id' => $task->id, 'user_id' => $supervisor->id, 'body' => 'Tambahkan uji untuk kasus kosong.']);

        $this->actingAs($supervisor)->patch("/kegiatan/tugas/{$task->id}", ['title' => 'Diubah supervisor'])->assertForbidden();
        $this->actingAs($supervisor)->delete("/kegiatan/tugas/{$task->id}")->assertForbidden();
        $this->assertDatabaseMissing('weekly_tasks', ['title' => 'Diubah supervisor']);

        $this->actingAs($this->user('supervisor2@kp.test'))->post("/tugas/{$task->id}/komentar", ['body' => 'x'])->assertForbidden();
    }

    public function test_supervisor_mengatur_meeting_untuk_beberapa_mahasiswa(): void
    {
        $supervisor = $this->user('supervisor@kp.test');
        $ids = Internship::supervisedBy($supervisor)->where('status', 'berjalan')->pluck('id')->all();

        $this->actingAs($supervisor)
            ->post('/meeting', ['magang' => $ids, 'day' => 'rabu', 'time' => '10:00', 'url' => 'https://meet.google.com/abc-defg-hij'])
            ->assertRedirect('/dashboard');

        foreach ($ids as $id) {
            $this->assertDatabaseHas('weekly_meetings', ['internship_id' => $id, 'day' => 'rabu', 'url' => 'https://meet.google.com/abc-defg-hij']);
        }
    }

    public function test_supervisor_tidak_bisa_mengatur_meeting_mahasiswa_lain(): void
    {
        $other = Internship::supervisedBy($this->user('supervisor2@kp.test'))->where('status', 'berjalan')->firstOrFail();

        $this->actingAs($this->user('supervisor@kp.test'))
            ->post('/meeting', ['magang' => [$other->id], 'day' => 'senin', 'time' => '09:00', 'url' => 'https://zoom.us/j/123'])
            ->assertForbidden();
    }

    public function test_dosen_pembimbing_mengatur_meeting_seminar(): void
    {
        $gilang = Internship::where('student_id', $this->user('gilang@kp.test')->id)->firstOrFail();
        $rina = Internship::where('student_id', $this->user('mahasiswa@kp.test')->id)->firstOrFail();

        $this->actingAs($this->user('dospem@kp.test'))
            ->post('/meeting', ['magang' => [$gilang->id], 'day' => 'jumat', 'time' => '13:00', 'url' => 'https://meet.google.com/sem-inar'])
            ->assertRedirect('/dashboard');
        $this->assertDatabaseHas('weekly_meetings', ['internship_id' => $gilang->id, 'day' => 'jumat']);

        // Magang dijadwalkan Company Supervisor, bukan dospem
        $this->actingAs($this->user('dospem@kp.test'))
            ->post('/meeting', ['magang' => [$rina->id], 'day' => 'jumat', 'time' => '13:00', 'url' => 'https://meet.google.com/x'])
            ->assertForbidden();
    }

    public function test_link_meeting_harus_http_atau_https(): void
    {
        $supervisor = $this->user('supervisor@kp.test');
        $id = Internship::supervisedBy($supervisor)->where('status', 'berjalan')->firstOrFail()->id;

        $this->actingAs($supervisor)
            ->post('/meeting', ['magang' => [$id], 'day' => 'senin', 'time' => '09:00', 'url' => 'javascript:alert(1)'])
            ->assertSessionHasErrors('url');
    }
}

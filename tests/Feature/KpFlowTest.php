<?php

namespace Tests\Feature;

use App\Models\Internship;
use App\Models\User;
use App\Models\WeeklyTask;
use Database\Seeders\TestingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

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

    public function test_tamu_diarahkan_ke_halaman_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_setiap_peran_bisa_masuk_dan_melihat_dashboard(): void
    {
        $emails = [
            'mahasiswa@kp.test',
            'mentor@kp.test',
            'divisi@kp.test',
            'dospem@kp.test',
            'kaprodi@kp.test',
            'biro@kp.test',
        ];

        foreach ($emails as $email) {
            $this->post('/login', ['email' => $email, 'password' => 'password'])
                ->assertRedirect('/dashboard');

            $this->get('/dashboard')->assertOk();

            $this->post('/logout');
        }
    }

    public function test_akun_nonaktif_tidak_bisa_masuk(): void
    {
        $this->user('mahasiswa@kp.test')->update(['is_active' => false]);

        $this->post('/login', ['email' => 'mahasiswa@kp.test', 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_mahasiswa_tidak_bisa_membuka_halaman_mentor(): void
    {
        $this->actingAs($this->user('mahasiswa@kp.test'))
            ->get('/mentor/tugas')
            ->assertForbidden();
    }

    public function test_hanya_kaprodi_yang_melihat_log_aktivitas(): void
    {
        $this->actingAs($this->user('mentor@kp.test'))->get('/log-aktivitas')->assertForbidden();
        $this->actingAs($this->user('kaprodi@kp.test'))->get('/log-aktivitas')->assertOk();
    }

    public function test_mentor_bisa_menambah_task_untuk_bimbingannya(): void
    {
        $mentor = $this->user('mentor@kp.test');
        $internship = Internship::mentoredBy($mentor)->firstOrFail();

        $this->actingAs($mentor)
            ->post('/mentor/tugas', [
                'magang' => $internship->id,
                'minggu' => 1,
                'day' => 'senin',
                'title' => 'Task uji coba',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('weekly_tasks', [
            'internship_id' => $internship->id,
            'title' => 'Task uji coba',
            'status' => 'todo',
        ]);
    }

    public function test_mentor_tidak_bisa_menambah_task_untuk_mahasiswa_lain(): void
    {
        $mentorA = $this->user('mentor@kp.test');
        $milikMentorB = Internship::mentoredBy($this->user('mentor2@kp.test'))->firstOrFail();

        $this->actingAs($mentorA)
            ->post('/mentor/tugas', [
                'magang' => $milikMentorB->id,
                'minggu' => 1,
                'day' => 'senin',
                'title' => 'Tidak boleh',
            ])
            ->assertForbidden();
    }

    public function test_mahasiswa_bisa_mengirim_bukti_untuk_task_sendiri(): void
    {
        $rina = $this->user('mahasiswa@kp.test');
        $task = WeeklyTask::whereHas('internship', fn ($q) => $q->where('student_id', $rina->id))->firstOrFail();

        $this->actingAs($rina)
            ->patch("/tugas/{$task->id}/bukti", ['evidence_url' => 'https://github.com/contoh/proyek/pull/99'])
            ->assertRedirect();

        $this->assertDatabaseHas('task_evidences', [
            'weekly_task_id' => $task->id,
            'submitted_by' => $rina->id,
            'url' => 'https://github.com/contoh/proyek/pull/99',
        ]);
    }

    public function test_mahasiswa_tidak_bisa_mengirim_bukti_untuk_task_orang_lain(): void
    {
        $rina = $this->user('mahasiswa@kp.test');
        $taskLain = WeeklyTask::whereHas('internship', fn ($q) => $q->where('student_id', '!=', $rina->id))->firstOrFail();

        $this->actingAs($rina)
            ->patch("/tugas/{$taskLain->id}/bukti", ['evidence_url' => 'https://example.com'])
            ->assertForbidden();
    }

    public function test_link_bukti_harus_http_atau_https(): void
    {
        $rina = $this->user('mahasiswa@kp.test');
        $task = WeeklyTask::whereHas('internship', fn ($q) => $q->where('student_id', $rina->id))->firstOrFail();

        $this->actingAs($rina)
            ->patch("/tugas/{$task->id}/bukti", ['evidence_url' => 'javascript:alert(1)'])
            ->assertSessionHasErrors('evidence_url');
    }

    public function test_mentor_mengatur_meeting_untuk_beberapa_mahasiswa(): void
    {
        $mentor = $this->user('mentor@kp.test');
        $ids = Internship::mentoredBy($mentor)->where('status', 'berjalan')->pluck('id')->all();

        $this->actingAs($mentor)
            ->post('/mentor/meeting', [
                'magang' => $ids,
                'day' => 'rabu',
                'time' => '10:00',
                'url' => 'https://meet.google.com/abc-defg-hij',
            ])
            ->assertRedirect('/dashboard');

        foreach ($ids as $id) {
            $this->assertDatabaseHas('weekly_meetings', [
                'internship_id' => $id,
                'day' => 'rabu',
                'url' => 'https://meet.google.com/abc-defg-hij',
            ]);
        }
    }

    public function test_mentor_tidak_bisa_mengatur_meeting_mahasiswa_lain(): void
    {
        $milikMentorB = Internship::mentoredBy($this->user('mentor2@kp.test'))->firstOrFail();

        $this->actingAs($this->user('mentor@kp.test'))
            ->post('/mentor/meeting', [
                'magang' => [$milikMentorB->id],
                'day' => 'senin',
                'time' => '09:00',
                'url' => 'https://zoom.us/j/123',
            ])
            ->assertForbidden();
    }

    public function test_link_meeting_harus_http_atau_https(): void
    {
        $mentor = $this->user('mentor@kp.test');
        $id = Internship::mentoredBy($mentor)->where('status', 'berjalan')->firstOrFail()->id;

        $this->actingAs($mentor)
            ->post('/mentor/meeting', [
                'magang' => [$id],
                'day' => 'senin',
                'time' => '09:00',
                'url' => 'javascript:alert(1)',
            ])
            ->assertSessionHasErrors('url');
    }

    public function test_detail_task_hanya_untuk_pemilik_dan_mentornya(): void
    {
        $rina = $this->user('mahasiswa@kp.test');
        $task = WeeklyTask::whereHas('internship', fn ($q) => $q->where('student_id', $rina->id))->firstOrFail();

        $this->actingAs($rina)->get("/tugas/{$task->id}")->assertOk();
        $this->actingAs($this->user('mentor@kp.test'))->get("/tugas/{$task->id}")->assertOk();
        $this->actingAs($this->user('dimas@kp.test'))->get("/tugas/{$task->id}")->assertForbidden();
        $this->actingAs($this->user('mentor2@kp.test'))->get("/tugas/{$task->id}")->assertForbidden();
    }

    public function test_mentor_tidak_bisa_mengubah_task_pada_kp_yang_sudah_selesai(): void
    {
        $mentor = $this->user('mentor2@kp.test');
        $selesai = Internship::mentoredBy($mentor)->where('status', 'selesai')->firstOrFail();

        $this->actingAs($mentor)
            ->post('/mentor/tugas', ['magang' => $selesai->id, 'minggu' => 1, 'day' => 'senin', 'title' => 'Tidak boleh'])
            ->assertForbidden();
    }

    public function test_mentor_mengubah_status_dan_komentar_dari_detail_task(): void
    {
        $mentor = $this->user('mentor@kp.test');
        $task = WeeklyTask::whereHas('internship', fn ($q) => $q->mentoredBy($mentor)->where('status', 'berjalan'))->firstOrFail();

        $this->actingAs($mentor)
            ->patch("/mentor/tugas/{$task->id}", ['status' => 'completed', 'comment' => 'Sudah bagus'])
            ->assertRedirect();

        $this->assertDatabaseHas('weekly_tasks', ['id' => $task->id, 'status' => 'completed']);
        $this->assertDatabaseHas('task_comments', [
            'weekly_task_id' => $task->id,
            'user_id' => $mentor->id,
            'body' => 'Sudah bagus',
        ]);
    }
}

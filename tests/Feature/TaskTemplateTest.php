<?php

namespace Tests\Feature;

use App\Models\Internship;
use App\Models\TaskTemplate;
use App\Models\User;
use App\Models\WeeklyTask;
use Database\Seeders\TestingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Template task Company Supervisor: buat, ubah, terapkan ke mahasiswa magang, hapus. */
class TaskTemplateTest extends TestCase
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

    public function test_supervisor_membuat_dan_menerapkan_template(): void
    {
        $fajar = $this->internshipOf('fajar@kp.test');
        $supervisor = $fajar->supervisor;

        $this->actingAs($supervisor)->get('/template-task')->assertOk()->assertSee('Belum ada template');

        $this->actingAs($supervisor)->post('/template-task', ['nama' => '', 'items' => []])->assertSessionHasErrorsIn('template', ['nama', 'items']);
        $this->actingAs($supervisor)->post('/template-task', [
            'nama' => 'Onboarding',
            'items' => [
                ['minggu' => 1, 'hari' => 'senin', 'judul' => 'Perkenalan tim'],
                ['minggu' => 1, 'hari' => 'rabu', 'judul' => 'Instal alat kerja'],
                ['minggu' => 1, 'hari' => 'senin', 'judul' => ''],   // baris kosong diabaikan
            ],
        ])->assertSessionHasNoErrors();

        $template = TaskTemplate::where('owner_id', $supervisor->id)->firstOrFail();
        $this->assertSame(2, $template->items()->count());
        $this->actingAs($supervisor)->get('/template-task')->assertSee('Onboarding')->assertSee('Perkenalan tim');

        $before = WeeklyTask::where('internship_id', $fajar->id)->count();
        $this->actingAs($supervisor)->post("/template-task/{$template->id}/terapkan", ['mahasiswa' => [$fajar->id]])->assertSessionHasNoErrors();
        $this->assertSame($before + 2, WeeklyTask::where('internship_id', $fajar->id)->count());
        $this->assertTrue(WeeklyTask::where('internship_id', $fajar->id)->where('title', 'Perkenalan tim')->exists());
        $this->assertSame(1, $this->user('fajar@kp.test')->notifications()->where('data', 'like', '%task baru dari Company Supervisor%')->count());

        // Ubah lalu hapus; task yang sudah diterapkan tetap ada
        $this->actingAs($supervisor)->put("/template-task/{$template->id}", ['nama' => 'Onboarding v2', 'items' => [['minggu' => 2, 'hari' => 'jumat', 'judul' => 'Presentasi awal']]])->assertSessionHasNoErrors();
        $this->assertSame(['Presentasi awal'], $template->fresh()->items->pluck('title')->all());
        $this->actingAs($supervisor)->delete("/template-task/{$template->id}")->assertRedirect('/template-task');
        $this->assertTrue(WeeklyTask::where('internship_id', $fajar->id)->where('title', 'Perkenalan tim')->exists());
    }

    public function test_template_hanya_milik_dan_diterapkan_ke_mahasiswa_sendiri(): void
    {
        $fajar = $this->internshipOf('fajar@kp.test');
        $template = TaskTemplate::create(['owner_id' => $fajar->supervisor->id, 'name' => 'Rahasia']);
        $template->items()->create(['week_number' => 1, 'day' => 'senin', 'title' => 'X']);

        $other = User::where('role', 'supervisor')->where('id', '!=', $fajar->supervisor->id)->first();
        if ($other) {
            $this->actingAs($other)->post("/template-task/{$template->id}/terapkan", ['mahasiswa' => [$fajar->id]])->assertForbidden();
        }

        // Mahasiswa yang bukan bimbingannya diabaikan
        $rina = $this->internshipOf('mahasiswa@kp.test');
        if (! $rina->isSupervisedBy($fajar->supervisor)) {
            $this->actingAs($fajar->supervisor)->post("/template-task/{$template->id}/terapkan", ['mahasiswa' => [$rina->id]])->assertSessionHasErrorsIn("apply{$template->id}", 'mahasiswa');
        }

        $this->actingAs($this->user('dospem@kp.test'))->get('/template-task')->assertForbidden();
    }
}

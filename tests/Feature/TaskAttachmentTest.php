<?php

namespace Tests\Feature;

use App\Models\Internship;
use App\Models\StoredFile;
use App\Models\TaskAttachment;
use App\Models\User;
use App\Models\WeeklyTask;
use Database\Seeders\TestingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Lampiran materi lama (versi awal, saat task dibuat mentor). Sejak KP v2 tidak bisa ditambah lagi,
 * tetapi tetap bisa dibuka oleh mahasiswa pemilik, Company Supervisor, dosen pembimbing, dan kaprodi.
 */
class TaskAttachmentTest extends TestCase
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

    private function task(string $email): WeeklyTask
    {
        return Internship::where('student_id', $this->user($email)->id)->firstOrFail()->tasks()->orderBy('id')->firstOrFail();
    }

    private function attach(WeeklyTask $task, string $name = 'panduan.pdf'): TaskAttachment
    {
        $path = "lampiran/{$task->id}/".uniqid().'.pdf';
        Storage::disk('local')->put($path, '%PDF-1.4 contoh');
        $file = StoredFile::create([
            'disk' => 'local', 'path' => $path, 'original_name' => $name, 'mime_type' => 'application/pdf', 'size' => 15,
            'uploaded_by' => $this->user('supervisor@kp.test')->id,
        ]);

        return TaskAttachment::create(['weekly_task_id' => $task->id, 'file_id' => $file->id, 'uploaded_by' => $file->uploaded_by]);
    }

    public function test_lampiran_lama_bisa_dibuka_yang_berhak_saja(): void
    {
        $task = $this->task('mahasiswa@kp.test');
        $attachment = $this->attach($task);
        $url = "/tugas/{$task->id}/lampiran/{$attachment->id}";

        $this->actingAs($this->user('mahasiswa@kp.test'))->get($url)->assertOk();
        $this->actingAs($this->user('supervisor@kp.test'))->get($url.'?unduh=1')->assertOk()->assertDownload('panduan.pdf');
        $this->actingAs($this->user('dospem@kp.test'))->get($url)->assertOk();

        $this->actingAs($this->user('fajar@kp.test'))->get($url)->assertForbidden();        // mahasiswa lain
        $this->actingAs($this->user('supervisor2@kp.test'))->get($url)->assertForbidden();  // supervisor lain
        $this->post('/logout');
        $this->get($url)->assertRedirect('/login');                                          // tamu
    }

    public function test_lampiran_tidak_bisa_diambil_lewat_task_lain(): void
    {
        $attachment = $this->attach($this->task('mahasiswa@kp.test'));
        $fajarTask = $this->task('fajar@kp.test');

        $this->actingAs($this->user('fajar@kp.test'))
            ->get("/tugas/{$fajarTask->id}/lampiran/{$attachment->id}")
            ->assertNotFound();
    }

    public function test_lampiran_lama_tampil_di_detail_task_tanpa_tombol_unggah(): void
    {
        $task = $this->task('mahasiswa@kp.test');
        $this->attach($task, 'materi-lama.pdf');

        $this->actingAs($this->user('mahasiswa@kp.test'))->get("/tugas/{$task->id}")
            ->assertOk()->assertSee('Lampiran lama')->assertSee('materi-lama.pdf')->assertDontSee('Unggah lampiran');
    }

    public function test_tidak_ada_lagi_rute_untuk_menambah_lampiran(): void
    {
        $task = $this->task('mahasiswa@kp.test');

        $this->actingAs($this->user('supervisor@kp.test'))->post("/mentor/tugas/{$task->id}/lampiran")->assertNotFound();
        $this->actingAs($this->user('supervisor@kp.test'))->post('/mentor/tugas', ['title' => 'x'])->assertNotFound();
    }
}

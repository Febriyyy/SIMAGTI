<?php

namespace Tests\Feature;

use App\Models\Internship;
use App\Models\TaskEvidence;
use App\Models\User;
use App\Models\WeeklyTask;
use Database\Seeders\TestingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Mahasiswa mengirim bukti berupa berkas (foto, PDF, ZIP, ...), tidak hanya link. */
class TaskEvidenceFileTest extends TestCase
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

    private function rinaTask(): WeeklyTask
    {
        $rina = Internship::where('student_id', $this->user('mahasiswa@kp.test')->id)->firstOrFail();

        return $rina->tasks()->firstOrFail();
    }

    private function zip(): UploadedFile
    {
        return UploadedFile::fake()->create('source-code.zip', 2048, 'application/zip');
    }

    public function test_mahasiswa_mengunggah_foto_pdf_dan_zip_sebagai_bukti(): void
    {
        $task = $this->rinaTask();

        $this->actingAs($this->user('mahasiswa@kp.test'))
            ->patch("/tugas/{$task->id}/bukti", [
                'berkas' => [
                    UploadedFile::fake()->image('tampilan.png'),
                    UploadedFile::fake()->create('laporan.pdf', 500, 'application/pdf'),
                    $this->zip(),
                ],
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $files = TaskEvidence::where('weekly_task_id', $task->id)->where('type', 'file')->with('file')->get();
        $this->assertCount(3, $files);
        $this->assertEqualsCanonicalizing(['tampilan.png', 'laporan.pdf', 'source-code.zip'], $files->map->file->pluck('original_name')->all());
        foreach ($files as $evidence) {
            Storage::disk('local')->assertExists($evidence->file->path);
            $this->assertStringStartsWith("bukti/{$task->internship_id}/{$task->id}/", $evidence->file->path);
        }

        $this->get("/tugas/{$task->id}")->assertOk()->assertSee('source-code.zip')->assertSee('laporan.pdf');
    }

    public function test_link_dan_berkas_bisa_dikirim_sekaligus(): void
    {
        $task = $this->rinaTask();

        $this->actingAs($this->user('mahasiswa@kp.test'))
            ->patch("/tugas/{$task->id}/bukti", [
                'evidence_url' => 'https://github.com/contoh/proyek',
                'berkas' => [$this->zip()],
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('task_evidences', ['weekly_task_id' => $task->id, 'type' => 'link', 'url' => 'https://github.com/contoh/proyek']);
        $this->assertDatabaseHas('task_evidences', ['weekly_task_id' => $task->id, 'type' => 'file']);
    }

    public function test_harus_mengisi_link_atau_berkas(): void
    {
        $task = $this->rinaTask();

        $this->actingAs($this->user('mahasiswa@kp.test'))
            ->patch("/tugas/{$task->id}/bukti", [])
            ->assertSessionHasErrors('evidence_url');
    }

    public function test_program_dan_html_ditolak(): void
    {
        $task = $this->rinaTask();
        $student = $this->user('mahasiswa@kp.test');

        foreach ([['virus.exe', 'application/x-msdownload'], ['halaman.html', 'text/html'], ['gambar.svg', 'image/svg+xml']] as [$name, $mime]) {
            $this->actingAs($student)
                ->patch("/tugas/{$task->id}/bukti", ['berkas' => [UploadedFile::fake()->create($name, 10, $mime)]])
                ->assertSessionHasErrors('berkas.0');
        }

        // Ganti nama program menjadi .zip juga ditolak karena isinya tidak cocok
        $this->actingAs($student)
            ->patch("/tugas/{$task->id}/bukti", ['berkas' => [UploadedFile::fake()->create('aman.zip', 10, 'application/x-msdownload')]])
            ->assertSessionHasErrors('berkas.0');

        $this->assertSame(0, TaskEvidence::where('weekly_task_id', $task->id)->where('type', 'file')->count());
    }

    public function test_berkas_bukti_hanya_bisa_dibuka_pemilik_dan_supervisornya(): void
    {
        $task = $this->rinaTask();
        $this->actingAs($this->user('mahasiswa@kp.test'))
            ->patch("/tugas/{$task->id}/bukti", ['berkas' => [$this->zip()]]);
        $evidence = TaskEvidence::where('weekly_task_id', $task->id)->where('type', 'file')->firstOrFail();
        $url = "/tugas/{$task->id}/bukti/{$evidence->id}";

        $this->actingAs($this->user('mahasiswa@kp.test'))->get($url)->assertOk()->assertDownload('source-code.zip');
        $this->actingAs($this->user('supervisor@kp.test'))->get($url)->assertOk();
        $this->actingAs($this->user('nadia@kp.test'))->get($url)->assertForbidden();
        $this->actingAs($this->user('supervisor2@kp.test'))->get($url)->assertForbidden();

        // Bukti milik task lain tidak bisa dibuka lewat URL task ini
        $other = WeeklyTask::where('id', '!=', $task->id)->where('internship_id', $task->internship_id)->firstOrFail();
        $this->actingAs($this->user('mahasiswa@kp.test'))->get("/tugas/{$other->id}/bukti/{$evidence->id}")->assertNotFound();
    }

    public function test_mahasiswa_menghapus_bukti_sendiri_beserta_berkasnya(): void
    {
        $task = $this->rinaTask();
        $this->actingAs($this->user('mahasiswa@kp.test'))
            ->patch("/tugas/{$task->id}/bukti", ['berkas' => [$this->zip()]]);
        $evidence = TaskEvidence::where('weekly_task_id', $task->id)->where('type', 'file')->with('file')->firstOrFail();
        $path = $evidence->file->path;

        // Supervisor tidak boleh menghapus bukti mahasiswa
        $this->actingAs($this->user('supervisor@kp.test'))
            ->delete("/tugas/{$task->id}/bukti/{$evidence->id}")
            ->assertForbidden();

        $this->actingAs($this->user('mahasiswa@kp.test'))
            ->delete("/tugas/{$task->id}/bukti/{$evidence->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('task_evidences', ['id' => $evidence->id]);
        Storage::disk('local')->assertMissing($path);
    }
}

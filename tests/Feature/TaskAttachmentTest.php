<?php

namespace Tests\Feature;

use App\Models\Internship;
use App\Models\StoredFile;
use App\Models\TaskAttachment;
use App\Models\User;
use App\Models\WeeklyTask;
use Database\Seeders\TestingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Mentor melampirkan materi (PDF, PPT, gambar) pada task; hanya mahasiswa pemilik dan mentornya yang bisa mengunduh. */
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

    private function rinaTask(): WeeklyTask
    {
        $rina = Internship::where('student_id', $this->user('mahasiswa@kp.test')->id)->firstOrFail();

        return $rina->tasks()->firstOrFail();
    }

    private function pdf(string $name = 'panduan.pdf'): UploadedFile
    {
        return UploadedFile::fake()->create($name, 300, 'application/pdf');
    }

    private function pptx(): UploadedFile
    {
        return UploadedFile::fake()->create('materi.pptx', 800, 'application/vnd.openxmlformats-officedocument.presentationml.presentation');
    }

    public function test_mentor_membuat_task_beserta_lampiran_pdf_ppt_dan_gambar(): void
    {
        $rina = Internship::where('student_id', $this->user('mahasiswa@kp.test')->id)->firstOrFail();

        $this->actingAs($this->user('mentor@kp.test'))
            ->post('/mentor/tugas', [
                'magang' => $rina->id,
                'minggu' => $rina->currentWeek(),
                'day' => 'jumat',
                'title' => 'Pelajari alur checkout',
                'lampiran' => [$this->pdf(), $this->pptx(), UploadedFile::fake()->image('contoh.png')],
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $task = WeeklyTask::where('title', 'Pelajari alur checkout')->firstOrFail();
        $this->assertCount(3, $task->attachments);

        foreach ($task->attachments as $attachment) {
            Storage::disk('local')->assertExists($attachment->file->path);
            $this->assertNotEmpty($attachment->file->checksum);
        }
    }

    public function test_jenis_berkas_lain_dan_berkas_terlalu_besar_ditolak(): void
    {
        $task = $this->rinaTask();
        $mentor = $this->user('mentor@kp.test');

        $this->actingAs($mentor)
            ->post("/mentor/tugas/{$task->id}/lampiran", ['lampiran' => [UploadedFile::fake()->create('virus.exe', 10, 'application/x-msdownload')]])
            ->assertSessionHasErrors('lampiran.0');

        // Program yang diganti namanya menjadi .pdf tetap ditolak karena isinya bukan PDF
        $this->actingAs($mentor)
            ->post("/mentor/tugas/{$task->id}/lampiran", ['lampiran' => [UploadedFile::fake()->create('materi.pdf', 10, 'application/x-msdownload')]])
            ->assertSessionHasErrors('lampiran.0');

        $this->actingAs($mentor)
            ->post("/mentor/tugas/{$task->id}/lampiran", ['lampiran' => [UploadedFile::fake()->create('besar.pdf', TaskAttachment::MAX_KB + 1, 'application/pdf')]])
            ->assertSessionHasErrors('lampiran.0');

        $this->assertSame(0, TaskAttachment::count());
    }

    public function test_jumlah_lampiran_per_task_dibatasi(): void
    {
        $task = $this->rinaTask();
        $files = array_map(fn ($i) => $this->pdf("materi-{$i}.pdf"), range(1, TaskAttachment::MAX_FILES));

        $this->actingAs($this->user('mentor@kp.test'))
            ->post("/mentor/tugas/{$task->id}/lampiran", ['lampiran' => $files])
            ->assertSessionHasNoErrors();

        $this->post("/mentor/tugas/{$task->id}/lampiran", ['lampiran' => [$this->pdf('lebih.pdf')]])
            ->assertSessionHasErrors('lampiran');

        $this->assertSame(TaskAttachment::MAX_FILES, $task->attachments()->count());
    }

    public function test_mahasiswa_pemilik_dan_mentornya_bisa_mengunduh_orang_lain_tidak(): void
    {
        $task = $this->rinaTask();

        $this->actingAs($this->user('mentor@kp.test'))
            ->post("/mentor/tugas/{$task->id}/lampiran", ['lampiran' => [$this->pdf()]]);

        $attachment = $task->attachments()->firstOrFail();
        $url = "/tugas/{$task->id}/lampiran/{$attachment->id}";

        $this->actingAs($this->user('mahasiswa@kp.test'))->get($url)->assertOk();
        $this->actingAs($this->user('mentor@kp.test'))->get($url.'?unduh=1')->assertOk()->assertDownload('panduan.pdf');

        $this->actingAs($this->user('fajar@kp.test'))->get($url)->assertForbidden();     // mahasiswa lain
        $this->actingAs($this->user('mentor2@kp.test'))->get($url)->assertForbidden();   // mentor lain
        $this->post('/logout');
        $this->get($url)->assertRedirect('/login');                                       // tamu
    }

    public function test_lampiran_tidak_bisa_diambil_lewat_task_lain(): void
    {
        $task = $this->rinaTask();
        $this->actingAs($this->user('mentor@kp.test'))
            ->post("/mentor/tugas/{$task->id}/lampiran", ['lampiran' => [$this->pdf()]]);
        $attachment = $task->attachments()->firstOrFail();

        // Fajar mencoba memakai task miliknya sendiri untuk mengambil lampiran task Rina
        $fajarTask = Internship::where('student_id', $this->user('fajar@kp.test')->id)->firstOrFail()->tasks()->firstOrFail();

        $this->actingAs($this->user('fajar@kp.test'))
            ->get("/tugas/{$fajarTask->id}/lampiran/{$attachment->id}")
            ->assertNotFound();
    }

    public function test_mentor_lain_dan_mahasiswa_tidak_bisa_menambah_lampiran(): void
    {
        $task = $this->rinaTask();

        $this->actingAs($this->user('mentor2@kp.test'))
            ->post("/mentor/tugas/{$task->id}/lampiran", ['lampiran' => [$this->pdf()]])
            ->assertForbidden();

        $this->actingAs($this->user('mahasiswa@kp.test'))
            ->post("/mentor/tugas/{$task->id}/lampiran", ['lampiran' => [$this->pdf()]])
            ->assertForbidden();

        $this->assertSame(0, TaskAttachment::count());
    }

    public function test_kp_yang_sudah_selesai_tidak_bisa_diberi_lampiran(): void
    {
        $salsa = Internship::where('student_id', $this->user('salsa@kp.test')->id)->firstOrFail();
        $task = $salsa->tasks()->firstOrFail();

        $this->actingAs($this->user('mentor2@kp.test'))
            ->post("/mentor/tugas/{$task->id}/lampiran", ['lampiran' => [$this->pdf()]])
            ->assertForbidden();
    }

    public function test_mentor_menghapus_lampiran_beserta_berkasnya(): void
    {
        $task = $this->rinaTask();
        $this->actingAs($this->user('mentor@kp.test'))
            ->post("/mentor/tugas/{$task->id}/lampiran", ['lampiran' => [$this->pdf()]]);

        $attachment = $task->attachments()->with('file')->firstOrFail();
        $path = $attachment->file->path;

        $this->actingAs($this->user('mahasiswa@kp.test'))
            ->delete("/mentor/tugas/{$task->id}/lampiran/{$attachment->id}")
            ->assertForbidden();

        $this->actingAs($this->user('mentor@kp.test'))
            ->delete("/mentor/tugas/{$task->id}/lampiran/{$attachment->id}")
            ->assertRedirect();

        $this->assertSame(0, TaskAttachment::count());
        $this->assertNull(StoredFile::withTrashed()->find($attachment->file_id));
        Storage::disk('local')->assertMissing($path);
    }

    public function test_lampiran_tampil_di_detail_task_mahasiswa(): void
    {
        $task = $this->rinaTask();
        $this->actingAs($this->user('mentor@kp.test'))
            ->post("/mentor/tugas/{$task->id}/lampiran", ['lampiran' => [$this->pdf('panduan-modul.pdf')]]);

        $this->actingAs($this->user('mahasiswa@kp.test'))
            ->get("/tugas/{$task->id}")
            ->assertOk()
            ->assertSee('Materi dari mentor')
            ->assertSee('panduan-modul.pdf')
            ->assertDontSee('Unggah lampiran');
    }
}

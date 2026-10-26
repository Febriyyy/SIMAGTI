<?php

namespace Tests\Feature;

use App\Models\BoardColumn;
use App\Models\Internship;
use App\Models\Project;
use App\Models\User;
use App\Models\WeeklyTask;
use Database\Seeders\TestingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Kegiatan ala Jira/ClickUp: mahasiswa membuat proyek, task, kolom board, checklist; filter dan unduhan. */
class ActivityBoardTest extends TestCase
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

    private function column(Internship $internship, string $name): BoardColumn
    {
        return $internship->boardColumns()->where('name', $name)->firstOrFail();
    }

    public function test_board_dan_list_tampil_dengan_kolom_dan_kode_task(): void
    {
        $rina = $this->internshipOf('mahasiswa@kp.test');
        $task = $rina->tasks()->with('project')->orderBy('number')->first();

        $this->actingAs($this->user('mahasiswa@kp.test'))->get('/kegiatan')
            ->assertOk()
            ->assertSee('To Do')->assertSee('In Progress')->assertSee('Done')
            ->assertSee($task->key())
            ->assertSee('Task baru')
            ->assertSee('Goals');
        $this->assertSame(['To Do', 'In Progress', 'Done'], $rina->boardColumns()->pluck('name')->all(), 'Kolom Review dihapus');

        $this->get('/kegiatan?tampilan=list')->assertOk()->assertSee('Prioritas')->assertSee($task->title);
    }

    public function test_mahasiswa_membuat_task_dengan_kode_proyek_berurutan(): void
    {
        $rina = $this->internshipOf('mahasiswa@kp.test');
        $project = $rina->projects()->where('key', 'PMS')->firstOrFail();
        $plan = $rina->activityPlans()->where('title', 'Modul Pemesanan')->firstOrFail();
        $goal = $rina->goals()->firstOrFail();
        $next = (int) $rina->tasks()->max('number') + 1;

        // Task wajib di bawah rencana kegiatan
        $this->actingAs($this->user('mahasiswa@kp.test'))
            ->post('/kegiatan/tugas', ['title' => 'Tanpa rencana', 'project_id' => $project->id])
            ->assertSessionHasErrors('activity_plan_id');

        $this->actingAs($this->user('mahasiswa@kp.test'))
            ->post('/kegiatan/tugas', [
                'title' => 'Rancang halaman checkout',
                'activity_plan_id' => $plan->id,
                'goal_id' => $goal->id,
                'project_id' => $project->id,
                'priority' => 'high',
                'start_date' => now()->toDateString(),
                'due_date' => now()->addDays(3)->toDateString(),
                'labels' => 'ui, frontend, ui',
            ])->assertRedirect();

        $task = WeeklyTask::where('title', 'Rancang halaman checkout')->firstOrFail();
        $this->assertSame("PMS-{$next}", $task->key());
        $this->assertSame($plan->id, $task->activity_plan_id);
        $this->assertSame($goal->id, $task->goal_id);
        $this->assertSame('todo', $task->status);
        $this->assertSame(['ui', 'frontend'], $task->labels);
        $this->assertSame($rina->weekOf(now()->addDays(3)), $task->week_number);
        $this->assertSame($this->user('mahasiswa@kp.test')->id, $task->created_by);
    }

    public function test_task_tidak_bisa_memakai_proyek_atau_kolom_mahasiswa_lain(): void
    {
        $dimas = $this->internshipOf('dimas@kp.test');

        $this->actingAs($this->user('mahasiswa@kp.test'))
            ->post('/kegiatan/tugas', ['title' => 'Salah tempat', 'project_id' => $dimas->projects()->first()->id, 'board_column_id' => $dimas->boardColumns()->first()->id,
                'activity_plan_id' => $dimas->activityPlans()->first()->id, 'goal_id' => $dimas->goals()->first()->id])
            ->assertSessionHasErrors(['project_id', 'board_column_id', 'activity_plan_id', 'goal_id']);

        $plan = $this->internshipOf('mahasiswa@kp.test')->activityPlans()->first()->id;
        $this->actingAs($this->user('mahasiswa@kp.test'))
            ->post('/kegiatan/tugas', ['title' => 'Tenggat terbalik', 'activity_plan_id' => $plan, 'start_date' => now()->toDateString(), 'due_date' => now()->subDay()->toDateString()])
            ->assertSessionHasErrors('due_date');
    }

    public function test_memindah_task_mengubah_status_dan_tanggal_selesai(): void
    {
        $rina = $this->internshipOf('mahasiswa@kp.test');
        $task = $rina->tasks()->where('status', 'todo')->firstOrFail();
        $done = $this->column($rina, 'Done');

        $this->actingAs($this->user('mahasiswa@kp.test'))
            ->patchJson("/kegiatan/tugas/{$task->id}/pindah", ['board_column_id' => $done->id, 'position' => 0])
            ->assertOk()->assertJson(['ok' => true, 'status' => 'completed']);

        $fresh = $task->fresh();
        $this->assertSame('completed', $fresh->status);
        $this->assertNotNull($fresh->completed_at);
        $this->assertSame(0, $fresh->position);

        $this->patchJson("/kegiatan/tugas/{$task->id}/pindah", ['board_column_id' => $this->column($rina, 'In Progress')->id, 'position' => 0])
            ->assertJson(['status' => 'in_progress']);
        $this->assertNull($task->fresh()->completed_at);

        // Kolom board mahasiswa lain ditolak
        $this->patchJson("/kegiatan/tugas/{$task->id}/pindah", ['board_column_id' => $this->internshipOf('dimas@kp.test')->boardColumns()->first()->id, 'position' => 0])
            ->assertStatus(422);
    }

    public function test_mahasiswa_lain_dan_supervisor_tidak_bisa_memindah_task(): void
    {
        $rina = $this->internshipOf('mahasiswa@kp.test');
        $task = $rina->tasks()->firstOrFail();
        $payload = ['board_column_id' => $this->column($rina, 'Done')->id, 'position' => 0];

        $this->actingAs($this->user('dimas@kp.test'))->patchJson("/kegiatan/tugas/{$task->id}/pindah", $payload)->assertForbidden();
        $this->actingAs($this->user('supervisor@kp.test'))->patchJson("/kegiatan/tugas/{$task->id}/pindah", $payload)->assertForbidden();
    }

    public function test_kolom_bisa_ditambah_diubah_dan_dihapus_dengan_task_dipindahkan(): void
    {
        $rina = $this->internshipOf('mahasiswa@kp.test');
        $student = $this->user('mahasiswa@kp.test');

        $this->actingAs($student)->post('/kegiatan/kolom', ['name' => 'Blocked', 'category' => 'in_progress'])->assertRedirect();
        $blocked = $this->column($rina, 'Blocked');

        $task = $rina->tasks()->where('status', 'todo')->firstOrFail();
        $this->patchJson("/kegiatan/tugas/{$task->id}/pindah", ['board_column_id' => $blocked->id, 'position' => 0]);
        $this->assertSame('in_progress', $task->fresh()->status);

        // Arti kolom diganti: status task di dalamnya ikut
        $this->patch("/kegiatan/kolom/{$blocked->id}", ['category' => 'done'])->assertRedirect();
        $this->assertSame('completed', $task->fresh()->status);

        $this->delete("/kegiatan/kolom/{$blocked->id}")->assertRedirect();
        $this->assertDatabaseMissing('board_columns', ['id' => $blocked->id]);
        $this->assertSame($this->column($rina, 'Done')->id, $task->fresh()->board_column_id, 'Pindah ke kolom lain dengan arti sama');

        // Kolom milik mahasiswa lain tidak bisa disentuh
        $this->delete('/kegiatan/kolom/'.$this->internshipOf('dimas@kp.test')->boardColumns()->first()->id)->assertNotFound();
    }

    public function test_proyek_baru_mendapat_kode_unik_dan_bisa_diarsipkan(): void
    {
        $rina = $this->internshipOf('mahasiswa@kp.test');
        $student = $this->user('mahasiswa@kp.test');

        $this->actingAs($student)->post('/kegiatan/proyek', ['name' => 'Portal Mitra'])->assertRedirect();
        $this->actingAs($student)->post('/kegiatan/proyek', ['name' => 'Portal Mitra Baru', 'key' => 'pms'])->assertSessionHasErrors('key');
        $this->actingAs($student)->post('/kegiatan/proyek', ['name' => 'X', 'key' => 'A-B'])->assertSessionHasErrors('key');

        $project = Project::where('internship_id', $rina->id)->where('name', 'Portal Mitra')->firstOrFail();
        $this->assertMatchesRegularExpression('/^[A-Z0-9]{2,6}$/', $project->key);

        $this->patch("/kegiatan/proyek/{$project->id}", ['archived' => 1])->assertRedirect();
        $this->assertNotNull($project->fresh()->archived_at);

        $this->actingAs($this->user('dimas@kp.test'))->patch("/kegiatan/proyek/{$project->id}", ['archived' => 0])->assertNotFound();
    }

    public function test_checklist_task(): void
    {
        $task = $this->internshipOf('mahasiswa@kp.test')->tasks()->firstOrFail();
        $student = $this->user('mahasiswa@kp.test');

        $this->actingAs($student)->post("/tugas/{$task->id}/checklist", ['text' => 'Tulis unit test'])->assertRedirect();
        $item = $task->checklist()->where('text', 'Tulis unit test')->firstOrFail();

        $this->patch("/tugas/{$task->id}/checklist/{$item->id}", ['is_done' => 1])->assertRedirect();
        $this->assertTrue($item->fresh()->is_done);

        // Butir checklist harus milik task di URL
        $other = $this->internshipOf('mahasiswa@kp.test')->tasks()->whereKeyNot($task->id)->firstOrFail();
        $this->patch("/tugas/{$other->id}/checklist/{$item->id}", ['is_done' => 0])->assertNotFound();

        $this->delete("/tugas/{$task->id}/checklist/{$item->id}")->assertRedirect();
        $this->assertDatabaseMissing('task_checklist_items', ['id' => $item->id]);
    }

    public function test_ritme_kerja_mengubah_arti_periode_ini(): void
    {
        $rina = $this->internshipOf('mahasiswa@kp.test');

        $this->actingAs($this->user('mahasiswa@kp.test'))->patch('/kegiatan/pengaturan', ['cadence' => 'monthly'])->assertRedirect();
        $this->assertSame('monthly', $rina->fresh()->cadence);

        $this->get('/kegiatan')->assertOk()->assertSee(now()->locale('id')->translatedFormat('F Y'));
        $this->patch('/kegiatan/pengaturan', ['cadence' => 'tahunan'])->assertSessionHasErrors('cadence');
    }

    public function test_filter_task_per_proyek_status_dan_periode(): void
    {
        $rina = $this->internshipOf('mahasiswa@kp.test');
        $doc = $rina->projects()->where('key', 'DOC')->firstOrFail();
        $student = $this->user('mahasiswa@kp.test');

        $docTask = $rina->tasks()->where('project_id', $doc->id)->firstOrFail();
        $pmsTask = $rina->tasks()->where('project_id', '!=', $doc->id)->firstOrFail();

        $this->actingAs($student)->get("/kegiatan?tampilan=list&proyek={$doc->id}")
            ->assertOk()->assertSee($docTask->title)->assertDontSee($pmsTask->title);

        $done = $rina->tasks()->where('status', 'completed')->firstOrFail();
        $todo = $rina->tasks()->where('status', 'todo')->firstOrFail();
        $this->get('/kegiatan?tampilan=list&status=completed')->assertOk()->assertSee($done->title)->assertDontSee($todo->title);

        // Rentang tanggal: hanya task dengan tenggat di dalam rentang
        $dari = $todo->due_date->toDateString();
        $this->get("/kegiatan?tampilan=list&periode=rentang&dari={$dari}&sampai={$dari}")->assertOk()->assertSee($todo->title)->assertDontSee($done->title);
    }

    public function test_task_kp_yang_sudah_selesai_terkunci(): void
    {
        $salsa = $this->internshipOf('salsa@kp.test');
        $task = $salsa->tasks()->firstOrFail();

        $this->actingAs($this->user('salsa@kp.test'))->get('/kegiatan')->assertOk()->assertDontSee('Task baru');
        $this->post('/kegiatan/tugas', ['title' => 'Tidak boleh'])->assertForbidden();
        $this->patch("/kegiatan/tugas/{$task->id}", ['title' => 'Tidak boleh'])->assertForbidden();
    }

    public function test_unduh_kegiatan_excel_dan_cetak_pdf(): void
    {
        $student = $this->user('mahasiswa@kp.test');

        $response = $this->actingAs($student)->get('/kegiatan/unduh?format=xlsx')->assertOk();
        $this->assertStringContainsString('spreadsheetml', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('.xlsx', $response->headers->get('Content-Disposition'));

        $path = $response->baseResponse->getFile()->getPathname();
        $zip = new \ZipArchive;
        $this->assertTrue($zip->open($path) === true);
        $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        $this->assertStringContainsString('Rina Putri', $sheet);

        $this->get('/kegiatan/unduh?format=pdf')->assertOk()
            ->assertSee('Rina Putri')->assertSee('PT Nusantara Digital')->assertSee('Universitas Satya Mandala')
            ->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_supervisor_mengunduh_kegiatan_mahasiswanya_saja(): void
    {
        $rina = $this->internshipOf('mahasiswa@kp.test');
        $dimas = $this->internshipOf('dimas@kp.test');

        $this->actingAs($this->user('supervisor@kp.test'))->get("/pantau/{$rina->id}/unduh?format=xlsx")->assertOk();
        $this->actingAs($this->user('supervisor@kp.test'))->get("/pantau/{$dimas->id}/unduh?format=pdf")->assertForbidden();
        $this->actingAs($this->user('mahasiswa@kp.test'))->get("/pantau/{$rina->id}/unduh")->assertForbidden();
    }

    public function test_goals_di_halaman_kegiatan(): void
    {
        $rina = $this->internshipOf('mahasiswa@kp.test');
        $student = $this->user('mahasiswa@kp.test');

        $this->actingAs($student)->get('/kegiatan')->assertOk()->assertSee('Goals')->assertSee('Membangun REST API dengan Laravel')->assertSee('dari 4 tercapai')->assertSee('Goal tercapai otomatis');

        // Seeder: goal tercapai tepat bila semua task-nya Done
        foreach ($rina->goals()->withCount(['tasks', 'tasks as open_count' => fn ($q) => $q->where('status', '!=', 'completed')])->get() as $g) {
            $this->assertSame($g->tasks_count > 0 && $g->open_count === 0, $g->isAchieved(), $g->title);
        }

        $this->post('/kegiatan/goals', ['title' => ''])->assertSessionHasErrors('title');
        $this->post('/kegiatan/goals', ['title' => 'Memahami CI/CD'])->assertSessionHasNoErrors();
        $goal = $rina->goals()->where('title', 'Memahami CI/CD')->firstOrFail();

        // Tercapai tidak bisa dicentang manual: goal tanpa task belum tercapai
        $this->patch("/kegiatan/goals/{$goal->id}", ['achieved' => 1])->assertSessionHasNoErrors();
        $this->assertFalse($goal->fresh()->isAchieved());

        // Goal penuh dan tercapai otomatis saat semua task-nya Done, terbuka lagi bila ada yang dikembalikan
        $done = $rina->boardColumns()->where('category', 'done')->firstOrFail();
        $todo = $rina->boardColumns()->where('category', 'todo')->firstOrFail();
        $tasks = $rina->tasks()->whereNull('goal_id')->take(2)->get();
        if ($tasks->count() < 2) {
            $tasks = $rina->tasks()->take(2)->get();
        }
        $tasks->each->update(['goal_id' => $goal->id, 'board_column_id' => $todo->id]);
        $this->assertFalse($goal->fresh()->isAchieved());

        $this->patchJson("/kegiatan/tugas/{$tasks[0]->id}/pindah", ['board_column_id' => $done->id, 'position' => 0])
            ->assertOk()->assertJsonPath('goal.percent', 50)->assertJsonPath('goal.achieved', false);
        $this->patchJson("/kegiatan/tugas/{$tasks[1]->id}/pindah", ['board_column_id' => $done->id, 'position' => 0])
            ->assertOk()->assertJsonPath('goal.percent', 100)->assertJsonPath('goal.achieved', true);
        $this->assertTrue($goal->fresh()->isAchieved());

        $this->patchJson("/kegiatan/tugas/{$tasks[1]->id}/pindah", ['board_column_id' => $todo->id, 'position' => 0])->assertJsonPath('goal.achieved', false);
        $this->assertFalse($goal->fresh()->isAchieved());

        // Filter task per goal
        $first = $rina->goals()->orderBy('position')->first();
        $task = $rina->tasks()->where('goal_id', $first->id)->firstOrFail();
        $other = $rina->tasks()->where('goal_id', '!=', $first->id)->firstOrFail();
        $this->get("/kegiatan?tampilan=list&goal={$first->id}")->assertOk()->assertSee($task->title)->assertDontSee($other->title);

        // Goal milik orang lain tidak bisa diubah; supervisor hanya melihat
        $this->actingAs($this->user('dimas@kp.test'))->patch("/kegiatan/goals/{$goal->id}", ['title' => 'x'])->assertNotFound();
        $this->actingAs($this->user('supervisor@kp.test'))->post('/kegiatan/goals', ['title' => 'x'])->assertForbidden();

        $this->actingAs($student)->delete("/kegiatan/goals/{$first->id}")->assertSessionHasNoErrors();
        $this->assertNull($task->fresh()->goal_id, 'Task tetap ada, kaitannya dilepas');
    }
}

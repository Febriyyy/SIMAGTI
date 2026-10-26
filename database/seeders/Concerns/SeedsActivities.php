<?php

namespace Database\Seeders\Concerns;

use App\Models\ActivityLog;
use App\Models\ActivityPlan;
use App\Models\BoardColumn;
use App\Models\Goal;
use App\Models\Internship;
use App\Models\Project;
use App\Models\TaskComment;
use App\Models\TaskEvidence;
use App\Models\TimeEntry;
use App\Models\User;
use App\Models\WeeklyTask;
use Illuminate\Support\Carbon;

/**
 * Pengisi data kegiatan KP yang dipakai DemoSeeder (tes) dan DataSeeder (aplikasi):
 * goals, rencana kegiatan (timesheet / Gantt), proyek, task di board, catatan kegiatan harian, bukti,
 * dan komentar Company Supervisor.
 *
 * Hasilnya tetap (tidak acak sungguhan): angka "acak" diturunkan dari id penempatan,
 * jadi tes bisa memakai jumlah yang sama setiap kali dijalankan.
 */
trait SeedsActivities
{
    /**
     * Goals mahasiswa di halaman Kegiatan.
     *
     * @param  list<string|array{0: string, 1: string}>  $items  judul goal (atau [judul, keterangan])
     */
    protected function seedGoals(Internship $internship, array $items): void
    {
        foreach (array_values($items) as $i => $item) {
            [$title, $description] = is_array($item) ? $item : [$item, null];
            $goal = Goal::create([
                'internship_id' => $internship->id,
                'title' => $title,
                'description' => $description,
                'position' => $i,
                'created_by' => $internship->student_id,
            ]);
            $at = $internship->start_date->copy()->addDays(2)->setTime(9, 0)->min(now());
            $goal->forceFill(['created_at' => $at, 'updated_at' => $at])->saveQuietly();
        }
    }

    /**
     * Isi rencana kegiatan, board, dan catatan kegiatan dari tanggal mulai sampai hari ini (atau sampai tanggal selesai).
     * Rencana: orientasi di minggu pertama, satu rencana per proyek (rentang tanggal task-nya), dan penyusunan laporan
     * di dua minggu terakhir. Setiap task berada di bawah rencana proyeknya.
     *
     * @param  list<array{name: string, key: string, color: string, tasks: list<string|array{0: string, 1: string}>}>  $projects
     * @param  int  $diligence  0–100: seberapa rajin mencatat timesheet dan menyelesaikan task
     * @param  int  $idleDays  hari terakhir tanpa catatan apa pun (contoh mahasiswa pasif)
     */
    protected function seedActivities(Internship $internship, array $projects, ?User $supervisor, int $diligence = 90, int $idleDays = 0, ?Carbon $until = null): void
    {
        // Angka "acak" tetap per penempatan. Memakai mt_rand (hasil sama di Windows, Linux, PHP 32/64-bit),
        // bukan perkalian bilangan besar yang bisa meluap di sebagian instalasi PHP.
        mt_srand($internship->id * 7919);
        $rand = fn (int $min, int $max): int => mt_rand($min, $max);

        $columns = $internship->ensureBoard()->keyBy(fn (BoardColumn $c) => $c->name);
        $today = Carbon::today();
        $end = ($until ?? $today)->copy()->min($internship->end_date)->startOfDay();
        $lastActive = $end->copy()->subDays($idleDays);
        $totalDays = max(1, $internship->start_date->diffInDays($end) + 1);

        // Task disebar rata dari awal sampai beberapa minggu setelah hari ini.
        $all = [];
        foreach ($projects as $p) {
            $project = Project::create([
                'internship_id' => $internship->id,
                'name' => $p['name'],
                'key' => $p['key'],
                'color' => $p['color'],
                'description' => $p['description'] ?? null,
            ]);
            foreach ($p['tasks'] as $t) {
                $all[] = [$project, ...(is_array($t) ? $t : [$t, null])];
            }
        }

        $goals = $internship->goals()->get()->values();
        $finished = $until !== null; // KP yang sudah selesai: semua task tuntas
        $span = $totalDays + ($finished ? -3 : 21);
        $count = count($all);
        $tasks = [];
        $priorities = ['medium', 'high', 'medium', 'low', 'urgent', 'medium'];

        // Jadwal task dihitung dulu supaya rentang rencana per proyek bisa diketahui
        $schedule = [];
        foreach ($all as $i => [$project]) {
            $due = $internship->start_date->copy()->addDays((int) round(($i + 1) * $span / ($count + 1)))->min($internship->end_date);
            $start = $due->copy()->subDays($rand(2, 6))->max($internship->start_date);
            $schedule[$i] = [$start, $due];
        }

        $planAt = $internship->start_date->copy()->addDay()->setTime(9, 30)->min(now());
        $position = 0;
        $makePlan = function (string $title, Carbon $from, Carbon $to, ?string $description = null, ?Goal $goal = null) use ($internship, $planAt, &$position): ActivityPlan {
            $plan = ActivityPlan::create([
                'internship_id' => $internship->id,
                'goal_id' => $goal?->id,
                'title' => $title,
                'description' => $description,
                'start_date' => $from->copy()->max($internship->start_date)->toDateString(),
                'end_date' => $to->copy()->min($internship->end_date)->max($from)->toDateString(),
                'position' => $position++,
                'created_by' => $internship->student_id,
            ]);
            $plan->forceFill(['created_at' => $planAt, 'updated_at' => $planAt])->saveQuietly();

            return $plan;
        };

        $makePlan('Orientasi dan pengenalan sistem', $internship->start_date->copy(), $internship->start_date->copy()->addDays(6), 'Mengenal tim, alur kerja, dan sistem yang dipakai perusahaan.');
        $plans = [];
        foreach ($all as $i => [$project]) {
            $key = $project->id;
            [$start, $due] = $schedule[$i];
            $plans[$key] = isset($plans[$key])
                ? [$plans[$key][0]->min($start), $plans[$key][1]->max($due), $project]
                : [$start->copy(), $due->copy(), $project];
        }
        $planOf = [];
        foreach (array_values($plans) as $k => [$from, $to, $project]) {
            $goal = $goals->isNotEmpty() ? $goals[$k % $goals->count()] : null;
            $planOf[$project->id] = $makePlan($project->name, $from, $to, $project->description, $goal);
        }
        $makePlan('Penyusunan laporan akhir', $internship->end_date->copy()->subDays(13), $internship->end_date->copy(), 'Merangkum kegiatan dan hasil kerja untuk laporan KP.');

        foreach ($all as $i => [$project, $title, $description]) {
            [$start, $due] = $schedule[$i];

            // Status menurut tenggat: lewat jauh = Done, dekat = In Progress, nanti = To Do.
            $daysToDue = $today->diffInDays($due, false);
            $doneChance = $diligence;
            $column = match (true) {
                $finished => 'Done',
                $due->lt($lastActive) && $rand(1, 100) <= $doneChance => 'Done',
                $daysToDue < -3 => 'In Progress',
                $daysToDue <= 2 => 'In Progress',
                $daysToDue <= 9 => $rand(0, 2) === 0 ? 'In Progress' : 'To Do',
                default => 'To Do',
            };
            if ($start->gt($lastActive)) {
                $column = 'To Do';
            }

            $task = WeeklyTask::create([
                'internship_id' => $internship->id,
                'project_id' => $project->id,
                'board_column_id' => $columns[$column]->id,
                'activity_plan_id' => $planOf[$project->id]->id,
                'goal_id' => $goals->isNotEmpty() ? $goals[$i % $goals->count()]->id : null,
                'title' => $title,
                'description' => $description,
                'priority' => $priorities[$i % count($priorities)],
                'start_date' => $start,
                'due_date' => $due,
                'labels' => $i % 4 === 0 ? ['dokumentasi'] : ($i % 5 === 1 ? ['bug'] : null),
                'position' => $i,
                'created_by' => $internship->student_id,
            ]);

            $task->forceFill([
                'created_at' => $start->copy()->setTime(8, 30),
                'updated_at' => $start->copy()->setTime(8, 30),
                'completed_at' => $column === 'Done' ? $due->copy()->subDays($rand(0, 2))->setTime(16, 0)->min(now()) : null,
            ])->saveQuietly();

            if ($i % 3 === 0) {
                foreach (['Analisis kebutuhan', 'Kerjakan', 'Uji dan rapikan'] as $k => $text) {
                    $task->checklist()->create(['text' => $text, 'is_done' => $column === 'Done' || ($column !== 'To Do' && $k === 0), 'position' => $k]);
                }
            }

            if ($column === 'Done') {
                $when = $task->completed_at->copy();
                $evidence = TaskEvidence::create([
                    'weekly_task_id' => $task->id,
                    'submitted_by' => $internship->student_id,
                    'type' => 'link',
                    'title' => 'Hasil '.$task->key(),
                    'url' => 'https://github.com/contoh/'.strtolower($project->key).'/pull/'.($task->number + 10),
                ]);
                $evidence->forceFill(['created_at' => $when, 'updated_at' => $when])->saveQuietly();

                if ($supervisor && $i % 2 === 0) {
                    $comment = TaskComment::create([
                        'weekly_task_id' => $task->id,
                        'user_id' => $supervisor->id,
                        'body' => self::FEEDBACK[$i % count(self::FEEDBACK)],
                    ]);
                    $at = $when->copy()->addHours(3)->min(now());
                    $comment->forceFill(['created_at' => $at, 'updated_at' => $at])->saveQuietly();
                }
            }

            $tasks[] = $task;
        }

        // Catatan kegiatan: hari kerja dari mulai sampai hari terakhir aktif (disisipkan sekaligus supaya cepat).
        $rows = [];
        for ($day = $internship->start_date->copy(); $day->lte($lastActive); $day->addDay()) {
            if ($day->isWeekend() || $rand(1, 100) > $diligence + 5) {
                continue;
            }

            $active = array_values(array_filter($tasks, fn (WeeklyTask $t) => $t->start_date->lte($day) && $t->due_date->copy()->addDays(2)->gte($day)));
            $entries = $rand(1, 2);
            $at = $day->copy()->setTime(17, 0)->min(now());

            for ($e = 0; $e < $entries; $e++) {
                $task = $active ? $active[$rand(0, count($active) - 1)] : null;

                $rows[] = [
                    'internship_id' => $internship->id,
                    'weekly_task_id' => $task?->id,
                    'work_date' => $day->toDateString(),
                    'description' => $task ? $this->logLine($task->title, $rand(0, 3)) : self::GENERAL[$rand(0, count(self::GENERAL) - 1)],
                    'file_id' => null,
                    'created_by' => $internship->student_id,
                    'created_at' => $at,
                    'updated_at' => $at,
                ];
            }
        }

        foreach (array_chunk($rows, 200) as $chunk) {
            TimeEntry::insert($chunk);
        }

        $this->seedActivityLogs($internship, $tasks, $rows, $lastActive);

        // Goal tercapai bila semua task-nya Done; tanggalnya ikut tanggal selesai task terakhir.
        foreach ($internship->goals()->pluck('id') as $goalId) {
            Goal::query()->whereKey($goalId)->update(['achieved_at' => null]);
            Goal::syncAchievement($goalId);
        }
    }

    /**
     * Riwayat aktivitas 30 hari terakhir untuk activity monitor (catatan kegiatan, task dibuat dan selesai, bukti),
     * seperti yang tercatat bila mahasiswa memakai aplikasi sungguhan.
     *
     * @param  list<WeeklyTask>  $tasks
     * @param  list<array<string, mixed>>  $entries
     */
    private function seedActivityLogs(Internship $internship, array $tasks, array $entries, Carbon $lastActive): void
    {
        $since = Carbon::today()->subDays(29);
        $taskType = (new WeeklyTask)->getMorphClass();
        $logs = [];
        $log = function (string $action, string $description, Carbon $at, ?int $subjectId = null) use (&$logs, $internship, $taskType) {
            $at = $at->copy()->min(now());
            $logs[] = [
                'user_id' => $internship->student_id, 'action' => $action, 'description' => $description,
                'subject_type' => $subjectId ? $taskType : null, 'subject_id' => $subjectId,
                'ip_address' => null, 'user_agent' => null, 'properties' => null, 'created_at' => $at, 'updated_at' => $at,
            ];
        };

        foreach ($tasks as $task) {
            if ($task->created_at->gte($since) && $task->created_at->lte($lastActive->copy()->endOfDay())) {
                $log('task.buat', "Menambah task {$task->key()} \"{$task->title}\"", $task->created_at, $task->id);
            }
            if ($task->completed_at && $task->completed_at->gte($since)) {
                $log('bukti.kirim', "Mengirim bukti untuk task {$task->key()} \"{$task->title}\"", $task->completed_at->copy()->subMinutes(20), $task->id);
                $log('task.selesai', "Menyelesaikan {$task->key()} \"{$task->title}\"", $task->completed_at, $task->id);
            }
        }

        foreach ($entries as $entry) {
            $at = Carbon::parse($entry['created_at']);
            if ($at->gte($since)) {
                $log('timesheet.catat', 'Mencatat kegiatan '.$at->locale('id')->translatedFormat('j M').': '.mb_strimwidth($entry['description'], 0, 80, '…'), $at);
            }
        }

        foreach (array_chunk($logs, 200) as $chunk) {
            ActivityLog::insert($chunk);
        }
    }

    private function logLine(string $title, int $variant): string
    {
        return [
            'Mengerjakan '.lcfirst($title),
            'Melanjutkan '.lcfirst($title).' dan diskusi dengan tim',
            'Revisi '.lcfirst($title).' sesuai masukan supervisor',
            'Menguji hasil '.lcfirst($title),
        ][$variant];
    }

    private const FEEDBACK = [
        'Sudah sesuai, lanjutkan.',
        'Hasilnya rapi. Tambahkan catatan singkat di dokumentasi tim.',
        'Bagus. Minggu depan coba kerjakan bagian yang lebih kompleks.',
        'Perhatikan penamaan agar konsisten dengan standar tim.',
    ];

    private const GENERAL = [
        'Daily stand-up dan menyusun rencana kerja',
        'Rapat koordinasi tim dan mencatat hasil diskusi',
        'Mempelajari dokumentasi internal',
        'Membantu tim menyiapkan bahan presentasi',
    ];
}

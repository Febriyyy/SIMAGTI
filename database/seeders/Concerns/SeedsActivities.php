<?php

namespace Database\Seeders\Concerns;

use App\Models\BoardColumn;
use App\Models\Internship;
use App\Models\Project;
use App\Models\SyllabusItem;
use App\Models\TaskComment;
use App\Models\TaskEvidence;
use App\Models\TimeEntry;
use App\Models\User;
use App\Models\WeeklyTask;
use Illuminate\Support\Carbon;

/**
 * Pengisi data kegiatan KP v2 yang dipakai DemoSeeder (tes) dan DataSeeder (aplikasi):
 * silabus, proyek, task di board, timesheet harian, bukti, dan komentar Company Supervisor.
 *
 * Hasilnya tetap (tidak acak sungguhan): angka "acak" diturunkan dari id penempatan,
 * jadi tes bisa memakai jumlah yang sama setiap kali dijalankan.
 */
trait SeedsActivities
{
    /**
     * @param  array<string, list<string|array{0: string, 1: string}>>  $items  kategori (hard/soft/attitude) => daftar tujuan
     */
    protected function seedSyllabus(Internship $internship, array $items, string $status, ?User $reviewer = null, ?string $note = null): void
    {
        $position = 0;
        foreach ($items as $category => $list) {
            foreach ($list as $item) {
                [$title, $description] = is_array($item) ? $item : [$item, null];
                SyllabusItem::create([
                    'internship_id' => $internship->id,
                    'category' => $category,
                    'title' => $title,
                    'description' => $description,
                    'position' => $position++,
                ]);
            }
        }

        $reviewedAt = $internship->start_date->copy()->addDays(6)->setTime(14, 0);
        $internship->forceFill([
            'syllabus_status' => $status,
            'syllabus_note' => $note,
            'syllabus_reviewed_by' => in_array($status, ['approved', 'revision'], true) ? $reviewer?->id : null,
            'syllabus_reviewed_at' => in_array($status, ['approved', 'revision'], true) ? ($reviewedAt->isFuture() ? now()->subHour() : $reviewedAt) : null,
        ])->saveQuietly();
    }

    /**
     * Isi board dan timesheet dari tanggal mulai sampai hari ini (atau sampai tanggal selesai).
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

        $syllabus = $internship->syllabusItems()->get()->values();
        $finished = $until !== null; // KP yang sudah selesai: semua task tuntas
        $span = $totalDays + ($finished ? -3 : 21);
        $count = count($all);
        $tasks = [];
        $priorities = ['medium', 'high', 'medium', 'low', 'urgent', 'medium'];

        foreach ($all as $i => [$project, $title, $description]) {
            $due = $internship->start_date->copy()->addDays((int) round(($i + 1) * $span / ($count + 1)));
            $start = $due->copy()->subDays($rand(2, 6))->max($internship->start_date);

            // Status menurut tenggat: lewat jauh = Done, dekat = Review/In Progress, nanti = To Do.
            $daysToDue = $today->diffInDays($due, false);
            $doneChance = $diligence;
            $column = match (true) {
                $finished => 'Done',
                $due->lt($lastActive) && $rand(1, 100) <= $doneChance => 'Done',
                $daysToDue < -3 => 'In Progress',
                $daysToDue <= 2 => $rand(0, 1) ? 'Review' : 'In Progress',
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
                'syllabus_item_id' => $syllabus->isNotEmpty() ? $syllabus[$i % $syllabus->count()]->id : null,
                'title' => $title,
                'description' => $description,
                'priority' => $priorities[$i % count($priorities)],
                'start_date' => $start,
                'due_date' => $due,
                'estimate_hours' => [4, 6, 8, 12, 16][$i % 5],
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

        // Timesheet: hari kerja dari mulai sampai hari terakhir aktif (disisipkan sekaligus supaya cepat).
        $rows = [];
        for ($day = $internship->start_date->copy(); $day->lte($lastActive); $day->addDay()) {
            if ($day->isWeekend() || $rand(1, 100) > $diligence + 5) {
                continue;
            }

            $active = array_values(array_filter($tasks, fn (WeeklyTask $t) => $t->start_date->lte($day) && $t->due_date->copy()->addDays(2)->gte($day)));
            $entries = $rand(1, 2);
            $left = [8, 8, 7.5, 8, 6][$rand(0, 4)];
            $at = $day->copy()->setTime(17, 0)->min(now());

            for ($e = 0; $e < $entries; $e++) {
                $task = $active ? $active[$rand(0, count($active) - 1)] : null;
                $hours = $e === $entries - 1 ? $left : [2, 3, 3.5, 4][$rand(0, 3)];
                $left -= $hours;

                $rows[] = [
                    'internship_id' => $internship->id,
                    'weekly_task_id' => $task?->id,
                    'work_date' => $day->toDateString(),
                    'hours' => $hours,
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

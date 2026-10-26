<?php

namespace App\Support;

use App\Models\ActivityPlan;
use App\Models\Internship;
use App\Models\WeeklyTask;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Data Gantt rencana kegiatan satu penempatan (tampilan ala Gantt di aplikasi manajemen proyek):
 * - sumbu waktu per hari sepanjang periode KP (bulan, hari, akhir pekan, hari ini),
 * - satu baris per rencana kegiatan dengan task-task di bawahnya (bisa dilipat),
 * - posisi batang dalam satuan hari; lebar satu hari diatur CSS (--day), jadi zoom tidak perlu dihitung ulang server.
 * Dipakai Timesheet mahasiswa, tab Rencana di halaman pantau, dan dashboard mahasiswa (ringkas).
 */
class Gantt
{
    /**
     * @param  Collection<int, ActivityPlan>  $plans  sebaiknya sudah memuat tasks_count, completed_tasks_count, dan (bila $withTasks) relasi tasks
     * @return array{days: list<array>, months: list<array>, total: int, today: ?int, rows: list<array>}
     */
    public static function build(Internship $internship, Collection $plans, bool $withTasks = true): array
    {
        $start = $internship->start_date->copy()->startOfDay();
        $end = $internship->end_date->copy()->startOfDay();
        $total = max(1, (int) $start->diffInDays($end) + 1);
        $today = Carbon::today();
        $index = fn (CarbonInterface $date) => max(0, min($total - 1, (int) $start->diffInDays($date->copy()->startOfDay(), false)));

        $days = [];
        $months = [];
        for ($i = 0, $d = $start->copy(); $i < $total; $i++, $d->addDay()) {
            $days[] = [
                'date' => $d->toDateString(),
                'day' => $d->day,
                'weekday' => mb_substr($d->locale('id')->translatedFormat('D'), 0, 3),
                'weekend' => $d->isWeekend(),
                'monday' => $d->isMonday(),
                'today' => $d->isSameDay($today),
                'title' => $d->locale('id')->translatedFormat('l, j F Y'),
            ];
            if ($i === 0 || $d->day === 1) {
                $months[] = ['label' => $d->locale('id')->translatedFormat('F Y'), 'short' => $d->locale('id')->translatedFormat('M'), 'start' => $i, 'length' => 0];
            }
            $months[count($months) - 1]['length']++;
        }

        $rows = [];
        foreach ($plans->values() as $k => $plan) {
            $phase = $plan->phase($today);
            $percent = $plan->percent();
            $row = [
                'plan' => $plan,
                'color' => $plan->colorKey($k),
                'offset' => $index($plan->start_date),
                'length' => max(1, $index($plan->end_date) - $index($plan->start_date) + 1),
                'phase' => $phase,
                'percent' => $percent,
                'late' => $phase === 'past' && $plan->tasks_count > 0 && $percent < 100,
                'tasks' => [],
            ];

            if ($withTasks && $plan->relationLoaded('tasks')) {
                foreach ($plan->tasks as $task) {
                    $row['tasks'][] = self::taskRow($task, $index, $today);
                }
            }

            $rows[] = $row;
        }

        return [
            'days' => $days,
            'months' => $months,
            'total' => $total,
            'today' => $today->betweenIncluded($start, $end) ? $index($today) : null,
            'rows' => $rows,
        ];
    }

    private static function taskRow(WeeklyTask $task, callable $index, Carbon $today): array
    {
        $from = $task->start_date ?? $task->due_date;
        $to = $task->due_date ?? $task->start_date;

        return [
            'task' => $task,
            'offset' => $from ? $index($from) : null,
            'length' => $from ? max(1, $index($to) - $index($from) + 1) : null,
            'days' => $from ? (int) $from->diffInDays($to) + 1 : null,
            'done' => $task->isDone(),
            'late' => $task->isOverdue(),
        ];
    }
}

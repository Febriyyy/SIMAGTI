<?php

namespace App\Support;

use App\Models\Internship;
use App\Models\SyllabusItem;
use Illuminate\Support\Collection;

/** Ringkasan data satu bulan KP untuk refleksi dan penilaian bulanan (dihitung, tidak diketik ulang). */
class MonthlySummary
{
    /** @return array{hours: float, days: int, done: int, done_tasks: Collection, by_category: array<string, int>} */
    public static function for(Internship $internship, int $month): array
    {
        [$start, $end] = $internship->monthRange($month);

        $entries = $internship->timeEntries()
            ->whereBetween('work_date', [$start->toDateString(), $end->toDateTimeString()])
            ->get(['work_date', 'hours']);

        $done = $internship->tasks()->with(['syllabusItem', 'project'])
            ->where('status', 'completed')
            ->whereBetween('completed_at', [$start, $end])
            ->orderBy('completed_at')
            ->get();

        $byCategory = array_fill_keys(array_keys(SyllabusItem::CATEGORIES), 0);
        foreach ($done as $task) {
            if ($task->syllabusItem) {
                $byCategory[$task->syllabusItem->category]++;
            }
        }

        return [
            'hours' => round((float) $entries->sum('hours'), 2),
            'days' => $entries->map(fn ($e) => $e->work_date->toDateString())->unique()->count(),
            'done' => $done->count(),
            'done_tasks' => $done,
            'by_category' => $byCategory,
        ];
    }
}

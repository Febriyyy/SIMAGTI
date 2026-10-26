<?php

namespace App\Support;

use App\Models\Internship;
use App\Models\TimeEntry;
use App\Models\WeeklyTask;
use Illuminate\Support\Collection;

/**
 * Isi laporan kegiatan untuk diunduh: rencana kegiatan, catatan kegiatan harian (Form Absensi dan Aktivitas),
 * dan daftar task per proyek, mengikuti filter yang sedang aktif (periode, proyek, status, dan seterusnya).
 */
class ActivityReport
{
    /** @return array{plans: Collection, entries: Collection<int, TimeEntry>, tasks: Collection<int, WeeklyTask>, range: string} */
    public static function build(Internship $internship, TaskFilter $filter): array
    {
        $range = $filter->range();
        $projectId = $filter->values['proyek'] ?? null;

        $entries = $internship->timeEntries()
            ->with('task.project')
            ->when($range, fn ($q) => $q->whereBetween('work_date', [$range[0]->toDateString(), $range[1]->copy()->endOfDay()->toDateTimeString()]))
            ->when($projectId, fn ($q) => $q->whereHas('task', fn ($t) => $t->where('project_id', $projectId)))
            ->orderBy('work_date')
            ->orderBy('id')
            ->get();

        $tasks = $filter->apply($internship->tasks()->getQuery())
            ->with(['project', 'column', 'plan', 'goal'])
            ->get()
            ->sortBy(fn ($t) => sprintf('%s|%08d', $t->project?->name ?? "\u{FFFF}", $t->number))
            ->values();

        $label = $range
            ? $range[0]->copy()->locale('id')->translatedFormat('j F Y').' – '.$range[1]->copy()->locale('id')->translatedFormat('j F Y')
            : 'Seluruh periode KP ('.$internship->periodLabel().')';

        $plans = $internship->activityPlans()
            ->with('goal')
            ->withCount(['tasks', 'tasks as completed_tasks_count' => fn ($q) => $q->where('status', 'completed')])
            ->when($range, fn ($q) => $q->where('start_date', '<=', $range[1]->toDateString())->where('end_date', '>=', $range[0]->toDateString()))
            ->get();

        return ['plans' => $plans, 'entries' => $entries, 'tasks' => $tasks, 'range' => $label];
    }

    public static function xlsx(Internship $internship, array $report): string
    {
        $student = $internship->student;
        $title = 'Laporan kegiatan KP · '.$student->name.' ('.$student->nim.') · '.$internship->placeName().' · '.$report['range'];

        $plans = [['No', 'Rencana kegiatan', 'Mulai', 'Selesai', 'Hari', 'Goal', 'Task selesai']];
        foreach ($report['plans'] as $i => $p) {
            $plans[] = [
                $i + 1,
                $p->title,
                $p->start_date->format('d-m-Y'),
                $p->end_date->format('d-m-Y'),
                $p->days(),
                $p->goal?->title,
                $p->completed_tasks_count.'/'.$p->tasks_count,
            ];
        }

        $timesheet = [['No', 'Tanggal', 'Hari', 'Aktivitas pekerjaan', 'Task', 'Proyek']];
        foreach ($report['entries'] as $i => $e) {
            $timesheet[] = [
                $i + 1,
                $e->work_date->format('d-m-Y'),
                $e->work_date->copy()->locale('id')->translatedFormat('l'),
                $e->description,
                $e->task?->key(),
                $e->task?->project?->name,
            ];
        }

        $tasks = [['Kode', 'Task', 'Rencana', 'Proyek', 'Status', 'Prioritas', 'Mulai', 'Tenggat', 'Selesai', 'Goal']];
        foreach ($report['tasks'] as $t) {
            $tasks[] = [
                $t->key(),
                $t->title,
                $t->plan?->title,
                $t->project?->name,
                $t->column?->name ?? $t->statusLabel(),
                $t->priorityLabel(),
                $t->start_date?->format('d-m-Y'),
                $t->due_date?->format('d-m-Y'),
                $t->completed_at?->format('d-m-Y'),
                $t->goal?->title,
            ];
        }

        return (new SimpleXlsx)
            ->addSheet('Rencana', $plans, [5, 48, 12, 12, 7, 36, 12], $title)
            ->addSheet('Catatan kegiatan', $timesheet, [5, 12, 10, 60, 10, 22], $title)
            ->addSheet('Task', $tasks, [10, 48, 32, 22, 16, 10, 12, 12, 12, 36], $title)
            ->save();
    }
}

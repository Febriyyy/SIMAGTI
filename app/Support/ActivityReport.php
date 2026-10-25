<?php

namespace App\Support;

use App\Models\Internship;
use App\Models\TimeEntry;
use App\Models\WeeklyTask;
use Illuminate\Support\Collection;

/**
 * Isi laporan kegiatan untuk diunduh: timesheet (Form Absensi dan Aktivitas) dan daftar task per proyek,
 * mengikuti filter yang sedang aktif (periode, proyek, status, dan seterusnya).
 */
class ActivityReport
{
    /** @return array{entries: Collection<int, TimeEntry>, tasks: Collection<int, WeeklyTask>, range: string, hours: float} */
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
            ->with(['project', 'column', 'syllabusItem'])
            ->withSum('timeEntries as logged_hours', 'hours')
            ->get()
            ->sortBy(fn ($t) => sprintf('%s|%08d', $t->project?->name ?? "\u{FFFF}", $t->number))
            ->values();

        $label = $range
            ? $range[0]->copy()->locale('id')->translatedFormat('j F Y').' – '.$range[1]->copy()->locale('id')->translatedFormat('j F Y')
            : 'Seluruh periode KP ('.$internship->periodLabel().')';

        return ['entries' => $entries, 'tasks' => $tasks, 'range' => $label, 'hours' => (float) $entries->sum('hours')];
    }

    public static function xlsx(Internship $internship, array $report): string
    {
        $student = $internship->student;
        $title = 'Laporan kegiatan KP · '.$student->name.' ('.$student->nim.') · '.$internship->placeName().' · '.$report['range'];

        $timesheet = [['No', 'Tanggal', 'Hari', 'Aktivitas pekerjaan', 'Task', 'Proyek', 'Jam']];
        foreach ($report['entries'] as $i => $e) {
            $timesheet[] = [
                $i + 1,
                $e->work_date->format('d-m-Y'),
                $e->work_date->copy()->locale('id')->translatedFormat('l'),
                $e->description,
                $e->task?->key(),
                $e->task?->project?->name,
                (float) $e->hours,
            ];
        }
        $timesheet[] = ['', '', '', 'Total jam', '', '', (float) $report['hours']];

        $tasks = [['Kode', 'Task', 'Proyek', 'Status', 'Prioritas', 'Mulai', 'Tenggat', 'Selesai', 'Jam tercatat', 'Tujuan silabus', 'Kategori']];
        foreach ($report['tasks'] as $t) {
            $tasks[] = [
                $t->key(),
                $t->title,
                $t->project?->name,
                $t->column?->name ?? $t->statusLabel(),
                $t->priorityLabel(),
                $t->start_date?->format('d-m-Y'),
                $t->due_date?->format('d-m-Y'),
                $t->completed_at?->format('d-m-Y'),
                (float) ($t->logged_hours ?? 0),
                $t->syllabusItem?->title,
                $t->syllabusItem?->categoryLabel(),
            ];
        }

        return (new SimpleXlsx)
            ->addSheet('Timesheet', $timesheet, [5, 12, 10, 60, 10, 22, 7], $title)
            ->addSheet('Task', $tasks, [10, 48, 22, 16, 10, 12, 12, 12, 12, 40, 12], $title)
            ->save();
    }
}

<?php

namespace App\Support;

use App\Models\Internship;
use App\Models\WeeklyTask;
use Illuminate\Http\Request;

/**
 * Data board/list kegiatan satu penempatan, dipakai halaman mahasiswa dan halaman pantau (hanya baca).
 */
class BoardData
{
    /** @return array<string, mixed> */
    public static function build(Internship $internship, Request $request, array $defaults = []): array
    {
        $columns = $internship->ensureBoard();
        $filter = TaskFilter::fromRequest($internship, $request, $defaults);
        $view = $request->query('tampilan') === 'list' ? 'list' : 'board';
        $sort = in_array($request->query('urut'), ['tenggat', 'prioritas', 'terbaru', 'kode'], true) ? $request->query('urut') : 'tenggat';

        $query = $filter->apply($internship->tasks()->getQuery())
            ->with(['project', 'column', 'plan', 'goal'])
            ->withCount(['evidences', 'comments', 'checklist', 'checklist as checklist_done_count' => fn ($q) => $q->where('is_done', true)]);

        $tasks = $query->get();

        $tasks = match ($sort) {
            'prioritas' => $tasks->sortBy(fn (WeeklyTask $t) => [WeeklyTask::PRIORITIES[$t->priority][2] ?? 9, $t->due_date?->timestamp ?? PHP_INT_MAX]),
            'terbaru' => $tasks->sortByDesc('updated_at'),
            'kode' => $tasks->sortByDesc('number'),
            default => $tasks->sortBy(fn (WeeklyTask $t) => [$t->isDone() ? 1 : 0, $t->due_date?->timestamp ?? PHP_INT_MAX, $t->number]),
        };

        $grouped = $columns->mapWithKeys(fn ($c) => [$c->id => $tasks->where('board_column_id', $c->id)->sortBy('position')->values()]);

        return [
            'internship' => $internship,
            'columns' => $columns,
            'tasks' => $tasks->values(),
            'grouped' => $grouped,
            'filter' => $filter,
            'view' => $view,
            'sort' => $sort,
            'projects' => $internship->projects()->orderBy('archived_at')->orderBy('name')->get(),
            'plans' => $internship->activityPlans()->get(),
            'goals' => $internship->goals()->withCount(['tasks', 'tasks as completed_tasks_count' => fn ($q) => $q->where('status', 'completed')])->get(),
            'currentPeriod' => Period::for($internship),
            'summary' => [
                'total' => $tasks->count(),
                'done' => $tasks->where('status', 'completed')->count(),
                'overdue' => $tasks->filter->isOverdue()->count(),
            ],
        ];
    }
}

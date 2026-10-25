<?php

namespace App\Http\Controllers;

use App\Models\TaskChecklistItem;
use App\Models\WeeklyTask;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/** Checklist (subtask) di dalam satu task. */
class ChecklistController extends Controller
{
    public function store(Request $request, WeeklyTask $task): RedirectResponse
    {
        Gate::authorize('update', $task);
        $data = $request->validate(['text' => ['required', 'string', 'max:200']], [], ['text' => 'isi checklist']);

        if ($task->checklist()->count() >= 30) {
            return back()->withErrors(['text' => 'Maksimal 30 butir checklist per task.']);
        }

        $task->checklist()->create($data + ['position' => (int) $task->checklist()->max('position') + 1]);

        return back();
    }

    public function update(Request $request, WeeklyTask $task, TaskChecklistItem $item): RedirectResponse
    {
        Gate::authorize('update', $task);
        $data = $request->validate([
            'is_done' => ['sometimes', 'boolean'],
            'text' => ['sometimes', 'string', 'max:200'],
        ]);

        $item->update($data);

        return back();
    }

    public function destroy(WeeklyTask $task, TaskChecklistItem $item): RedirectResponse
    {
        Gate::authorize('update', $task);
        $item->delete();

        return back();
    }
}

<?php

namespace App\Http\Controllers;

use App\Actions\Tasks\CreateWeeklyTask;
use App\Actions\Tasks\UpdateWeeklyTask;
use App\Http\Requests\Tasks\StoreTaskRequest;
use App\Http\Requests\Tasks\UpdateTaskRequest;
use App\Models\Internship;
use App\Models\WeeklyTask;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MentorTaskController extends Controller
{
    public function index(Request $request): View
    {
        $internships = Internship::with('student')
            ->mentoredBy($request->user())
            ->where('status', 'berjalan')
            ->get();

        $selected = $internships->firstWhere('id', $request->integer('magang')) ?? $internships->first();

        if (! $selected) {
            return view('tugas.mentor', [
                'internships' => $internships,
                'selected' => null,
                'tasks' => collect(),
                'week' => 1,
            ]);
        }

        $week = $selected->clampWeek($request->integer('minggu') ?: null);

        $tasks = $selected->tasks()
            ->withCount(['evidences', 'comments', 'attachments'])
            ->where('week_number', $week)
            ->get()
            ->sortBy(fn (WeeklyTask $task) => $task->dayOrder())
            ->values();

        return view('tugas.mentor', compact('internships', 'selected', 'tasks', 'week'));
    }

    public function store(StoreTaskRequest $request, CreateWeeklyTask $createTask): RedirectResponse
    {
        $data = $request->validated();

        $createTask->handle($request->user(), $request->internship(), $data, $request->attachments());

        return redirect()
            ->route('mentor.tugas', ['magang' => $request->internship()->id, 'minggu' => $data['minggu']])
            ->with('status', 'Task ditambahkan.');
    }

    public function update(UpdateTaskRequest $request, WeeklyTask $task, UpdateWeeklyTask $updateTask): RedirectResponse
    {
        $data = $request->validated();

        $updateTask->handle($request->user(), $task, $data['status'], $data['comment'] ?? null);

        return back()->with('status', 'Perubahan tersimpan.');
    }
}

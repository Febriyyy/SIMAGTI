<?php

namespace App\Http\Controllers;

use App\Actions\Tasks\SubmitTaskEvidence;
use App\Http\Requests\Tasks\StoreEvidenceRequest;
use App\Models\Internship;
use App\Models\WeeklyTask;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentTaskController extends Controller
{
    public function index(Request $request): View
    {
        $internship = Internship::with('company')
            ->where('student_id', $request->user()->id)
            ->latest('start_date')
            ->first();

        if (! $internship) {
            return view('tugas.mahasiswa', ['internship' => null, 'tasks' => collect(), 'week' => 1]);
        }

        $week = $internship->clampWeek($request->integer('minggu') ?: null);

        $tasks = $internship->tasks()
            ->withCount(['evidences', 'comments', 'attachments'])
            ->where('week_number', $week)
            ->get()
            ->sortBy(fn (WeeklyTask $task) => $task->dayOrder())
            ->values();

        return view('tugas.mahasiswa', compact('internship', 'tasks', 'week'));
    }

    /** Mahasiswa melampirkan link bukti pengerjaan (GitHub, Drive, dan sebagainya). */
    public function evidence(StoreEvidenceRequest $request, WeeklyTask $task, SubmitTaskEvidence $submit): RedirectResponse
    {
        $data = $request->validated();

        $submit->handle($request->user(), $task, $data['evidence_url'], $data['title'] ?? null);

        return back()->with('status', 'Bukti pengerjaan tersimpan.');
    }
}

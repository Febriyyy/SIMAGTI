<?php

namespace App\Http\Controllers;

use App\Models\WeeklyTask;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class TaskDetailController extends Controller
{
    /** Detail satu task: dilihat mahasiswa pemiliknya atau mentornya (aturan di WeeklyTaskPolicy). */
    public function show(Request $request, WeeklyTask $task): View
    {
        $task->load('internship.student', 'internship.company', 'internship.mentor', 'evidences.submitter', 'comments.author', 'attachments.file');

        Gate::authorize('view', $task);

        $user = $request->user();
        $internship = $task->internship;

        $isStudent = $user->role === 'mahasiswa';
        $isMentor = $user->role === 'mentor';
        $locked = $internship->isLocked();

        return view('tugas.detail', compact('task', 'internship', 'isStudent', 'isMentor', 'locked'));
    }
}

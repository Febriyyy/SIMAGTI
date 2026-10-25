<?php

namespace App\Http\Controllers;

use App\Models\WeeklyTask;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class TaskDetailController extends Controller
{
    /** Detail satu task: mahasiswa pemilik (bisa mengubah), Company Supervisor dan dosen pembimbing (memantau, berkomentar). */
    public function show(Request $request, WeeklyTask $task): View
    {
        $task->load([
            'internship.student.studentProfile.studyProgram', 'internship.company', 'internship.supervisor', 'internship.lecturer',
            'project', 'column', 'syllabusItem', 'checklist', 'timeEntries.creator',
            'evidences.submitter', 'evidences.file', 'comments.author', 'attachments.file',
        ]);

        Gate::authorize('view', $task);

        $user = $request->user();
        $internship = $task->internship;
        $canEdit = $user->can('update', $task);
        $canComment = $user->can('comment', $task);
        $isStudent = $user->role === 'mahasiswa';
        $isMentor = ! $isStudent; // dipakai partial bukti (pesan kosong)
        $locked = $internship->isLocked();

        $backUrl = $isStudent ? route('kegiatan.index') : route('pantau.show', $internship);
        $columns = $canEdit ? $internship->ensureBoard() : collect();
        $projects = $canEdit ? $internship->projects()->get() : collect();
        $syllabusItems = $canEdit ? $internship->syllabusItems()->get() : collect();

        return view('tugas.detail', compact('task', 'internship', 'canEdit', 'canComment', 'isStudent', 'isMentor', 'locked', 'backUrl', 'columns', 'projects', 'syllabusItems'));
    }
}

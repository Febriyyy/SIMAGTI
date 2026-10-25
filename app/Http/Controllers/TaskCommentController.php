<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\TaskComment;
use App\Models\WeeklyTask;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/** Percakapan di task: mahasiswa, Company Supervisor, dan dosen pembimbing (catatan revisi tampil langsung di aplikasi). */
class TaskCommentController extends Controller
{
    public function store(Request $request, WeeklyTask $task): RedirectResponse
    {
        Gate::authorize('comment', $task);
        $data = $request->validate(['body' => ['required', 'string', 'max:2000']], [], ['body' => 'komentar']);

        TaskComment::create([
            'weekly_task_id' => $task->id,
            'user_id' => $request->user()->id,
            'body' => trim($data['body']),
        ]);

        ActivityLog::record('task.komentar', "Berkomentar di task {$task->key()}", $task);

        return back()->with('status', 'Komentar terkirim.');
    }
}

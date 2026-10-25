<?php

namespace App\Http\Controllers;

use App\Actions\Activities\CreateTask;
use App\Actions\Activities\MoveTask;
use App\Http\Requests\Activities\SaveTaskRequest;
use App\Models\ActivityLog;
use App\Models\WeeklyTask;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/** Task kegiatan mahasiswa: buat, ubah, pindah kolom (board), hapus. */
class TaskController extends Controller
{
    public function store(SaveTaskRequest $request, CreateTask $create): RedirectResponse
    {
        $task = $create->handle($request->user(), $request->internship(), $request->taskData());

        return $request->boolean('buka')
            ? redirect()->route('tugas.show', $task)->with('status', "Task {$task->key()} dibuat.")
            : back()->with('status', "Task {$task->key()} dibuat.");
    }

    public function update(SaveTaskRequest $request, WeeklyTask $task): RedirectResponse
    {
        $data = $request->taskData();

        if (array_key_exists('board_column_id', $data) && $data['board_column_id'] !== null && (int) $data['board_column_id'] !== (int) $task->board_column_id) {
            app(MoveTask::class)->handle($task, (int) $data['board_column_id'], PHP_INT_MAX);
            unset($data['board_column_id']);
        }

        $task->update($data);

        return back()->with('status', "Task {$task->key()} diperbarui.");
    }

    /** Drag-and-drop dari board: balasan JSON supaya halaman tidak perlu dimuat ulang. */
    public function move(Request $request, WeeklyTask $task, MoveTask $move): JsonResponse
    {
        Gate::authorize('update', $task);

        $data = $request->validate([
            'board_column_id' => ['required', 'integer'],
            'position' => ['required', 'integer', 'min:0', 'max:10000'],
        ]);

        $task = $move->handle($task, (int) $data['board_column_id'], (int) $data['position']);

        return response()->json(['ok' => true, 'status' => $task->status, 'key' => $task->key()]);
    }

    public function destroy(Request $request, WeeklyTask $task): RedirectResponse
    {
        Gate::authorize('update', $task);

        ActivityLog::record('task.hapus', "Menghapus task {$task->key()} \"{$task->title}\"", $task);
        $task->delete();

        return redirect()->route('kegiatan.index')->with('status', 'Task dihapus.');
    }
}

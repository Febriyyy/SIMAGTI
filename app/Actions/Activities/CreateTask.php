<?php

namespace App\Actions\Activities;

use App\Models\ActivityLog;
use App\Models\Internship;
use App\Models\User;
use App\Models\WeeklyTask;
use Illuminate\Support\Facades\DB;

/** Mahasiswa menambah task ke board-nya (kolom pertama bila tidak dipilih), di urutan paling bawah kolom. */
class CreateTask
{
    public function handle(User $student, Internship $internship, array $data): WeeklyTask
    {
        return DB::transaction(function () use ($student, $internship, $data) {
            $columns = $internship->ensureBoard();
            $columnId = $data['board_column_id'] ?? $columns->first()->id;

            $task = $internship->tasks()->create($data + [
                'board_column_id' => $columnId,
                'priority' => $data['priority'] ?? 'medium',
                'position' => (int) $internship->tasks()->where('board_column_id', $columnId)->max('position') + 1,
                'created_by' => $student->id,
            ]);

            ActivityLog::record('task.buat', "Menambah task {$task->key()} \"{$task->title}\"", $task);

            return $task;
        });
    }
}

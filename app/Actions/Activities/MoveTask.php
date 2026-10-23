<?php

namespace App\Actions\Activities;

use App\Models\ActivityLog;
use App\Models\BoardColumn;
use App\Models\WeeklyTask;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Pindahkan task ke kolom dan urutan tertentu (drag-and-drop di board). Urutan kolom tujuan dinomori ulang. */
class MoveTask
{
    public function handle(WeeklyTask $task, int $columnId, int $index): WeeklyTask
    {
        $column = BoardColumn::where('internship_id', $task->internship_id)->find($columnId);

        if (! $column) {
            throw ValidationException::withMessages(['board_column_id' => 'Kolom tidak ditemukan di board ini.']);
        }

        return DB::transaction(function () use ($task, $column, $index) {
            $fromStatus = $task->status;
            $moved = (int) $task->board_column_id !== (int) $column->id;

            $siblings = WeeklyTask::where('internship_id', $task->internship_id)
                ->where('board_column_id', $column->id)
                ->whereKeyNot($task->id)
                ->orderBy('position')
                ->pluck('id')
                ->all();

            array_splice($siblings, max(0, min($index, count($siblings))), 0, [$task->id]);

            $task->board_column_id = $column->id;
            $task->position = array_search($task->id, $siblings, true);
            $task->save();

            foreach ($siblings as $position => $id) {
                if ($id !== $task->id) {
                    WeeklyTask::whereKey($id)->update(['position' => $position]);
                }
            }

            if ($moved) {
                ActivityLog::record('task.pindah', "Memindahkan {$task->key()} ke kolom {$column->name}", $task);
            }

            if ($fromStatus !== $task->status && $task->status === 'completed') {
                ActivityLog::record('task.selesai', "Menyelesaikan {$task->key()} \"{$task->title}\"", $task);
            }

            return $task;
        });
    }
}

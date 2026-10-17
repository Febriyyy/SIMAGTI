<?php

namespace App\Actions\Tasks;

use App\Models\ActivityLog;
use App\Models\TaskComment;
use App\Models\User;
use App\Models\WeeklyTask;
use Illuminate\Support\Facades\DB;

/** Mentor mengubah status task; komentar baru ditambahkan ke percakapan, tidak menimpa yang lama. */
class UpdateWeeklyTask
{
    public function handle(User $actor, WeeklyTask $task, string $status, ?string $comment = null): WeeklyTask
    {
        return DB::transaction(function () use ($actor, $task, $status, $comment) {
            $task->update(['status' => $status]);

            if (filled($comment)) {
                TaskComment::create([
                    'weekly_task_id' => $task->id,
                    'user_id' => $actor->id,
                    'body' => trim($comment),
                ]);
            }

            ActivityLog::record(
                'task.ubah',
                "Mengubah task \"{$task->title}\" menjadi {$task->statusLabel()}",
                $task
            );

            return $task;
        });
    }
}

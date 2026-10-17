<?php

namespace App\Actions\Tasks;

use App\Models\ActivityLog;
use App\Models\TaskEvidence;
use App\Models\User;
use App\Models\WeeklyTask;

/** Mahasiswa melampirkan link bukti pengerjaan (GitHub, Drive, dan sebagainya). */
class SubmitTaskEvidence
{
    public function handle(User $actor, WeeklyTask $task, string $url, ?string $title = null): TaskEvidence
    {
        $evidence = TaskEvidence::create([
            'weekly_task_id' => $task->id,
            'submitted_by' => $actor->id,
            'type' => 'link',
            'title' => $title,
            'url' => $url,
        ]);

        ActivityLog::record('bukti.kirim', "Mengirim bukti untuk task \"{$task->title}\"", $task);

        return $evidence;
    }
}

<?php

namespace App\Actions\Tasks;

use App\Models\ActivityLog;
use App\Models\Internship;
use App\Models\User;
use App\Models\WeeklyTask;

/** Mentor menambah satu task pada penempatan bimbingannya. Hak akses dicek sebelum Action ini dipanggil. */
class CreateWeeklyTask
{
    /**
     * @param  array{minggu: int, day: string, title: string, description?: ?string}  $data
     */
    public function handle(User $actor, Internship $internship, array $data): WeeklyTask
    {
        $task = WeeklyTask::create([
            'internship_id' => $internship->id,
            'week_number' => $data['minggu'],
            'day' => $data['day'],
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'status' => 'todo',
            'created_by' => $actor->id,
        ]);

        ActivityLog::record(
            'task.buat',
            "Menambah task \"{$task->title}\" untuk {$internship->student->name} (minggu {$task->week_number})",
            $task
        );

        return $task;
    }
}

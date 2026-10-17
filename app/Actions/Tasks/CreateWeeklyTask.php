<?php

namespace App\Actions\Tasks;

use App\Models\ActivityLog;
use App\Models\Internship;
use App\Models\User;
use App\Models\WeeklyTask;
use Illuminate\Support\Facades\DB;

/** Mentor menambah satu task (boleh beserta materi lampiran) pada penempatan bimbingannya. Hak akses dicek sebelum Action ini dipanggil. */
class CreateWeeklyTask
{
    public function __construct(private AttachTaskFiles $attach) {}

    /**
     * @param  array{minggu: int, day: string, title: string, description?: ?string}  $data
     * @param  list<\Illuminate\Http\UploadedFile>  $files
     */
    public function handle(User $actor, Internship $internship, array $data, array $files = []): WeeklyTask
    {
        return DB::transaction(fn () => $this->create($actor, $internship, $data, $files));
    }

    private function create(User $actor, Internship $internship, array $data, array $files): WeeklyTask
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

        $this->attach->handle($actor, $task, $files);

        return $task;
    }
}

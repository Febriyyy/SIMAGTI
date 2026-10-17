<?php

namespace App\Actions\Tasks;

use App\Models\ActivityLog;
use App\Models\TaskAttachment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/** Mentor menghapus satu lampiran: catatan di database dan berkasnya di penyimpanan. */
class RemoveTaskAttachment
{
    public function handle(TaskAttachment $attachment): void
    {
        $file = $attachment->file;
        $task = $attachment->task;

        DB::transaction(function () use ($attachment, $file, $task) {
            $attachment->delete();
            $file?->forceDelete();

            ActivityLog::record('lampiran.hapus', "Menghapus lampiran \"{$file?->original_name}\" dari task \"{$task->title}\"", $task);
        });

        if ($file) {
            Storage::disk($file->disk)->delete($file->path);
        }
    }
}

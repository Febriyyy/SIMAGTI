<?php

namespace App\Actions\Tasks;

use App\Models\ActivityLog;
use App\Models\TaskEvidence;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/** Mahasiswa menghapus bukti yang salah kirim (selama KP belum dikunci). */
class RemoveTaskEvidence
{
    public function handle(TaskEvidence $evidence): void
    {
        $file = $evidence->file;
        $task = $evidence->task;

        DB::transaction(function () use ($evidence, $file, $task) {
            $evidence->delete();
            $file?->forceDelete();

            ActivityLog::record('bukti.hapus', "Menghapus bukti \"{$evidence->label()}\" dari task \"{$task->title}\"", $task);
        });

        // Berkas fisik dihapus setelah database berhasil.
        if ($file) {
            Storage::disk($file->disk)->delete($file->path);
        }
    }
}

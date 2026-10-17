<?php

namespace App\Actions\Tasks;

use App\Models\ActivityLog;
use App\Models\StoredFile;
use App\Models\TaskAttachment;
use App\Models\User;
use App\Models\WeeklyTask;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Menyimpan materi dari mentor (PDF, PPT, gambar) sebagai lampiran task.
 * Berkas disimpan di disk privat, hanya bisa diunduh lewat aplikasi oleh yang berhak.
 */
class AttachTaskFiles
{
    public const DISK = 'local';

    /** @param  list<UploadedFile>  $files */
    public function handle(User $actor, WeeklyTask $task, array $files): void
    {
        if ($files === []) {
            return;
        }

        if ($task->attachments()->count() + count($files) > TaskAttachment::MAX_FILES) {
            throw ValidationException::withMessages([
                'lampiran' => 'Satu task maksimal '.TaskAttachment::MAX_FILES.' lampiran.',
            ]);
        }

        $stored = [];

        try {
            DB::transaction(function () use ($actor, $task, $files, &$stored) {
                foreach ($files as $upload) {
                    $path = $upload->store("tugas/{$task->internship_id}/{$task->id}", self::DISK);
                    $stored[] = $path;

                    $file = StoredFile::create([
                        'disk' => self::DISK,
                        'path' => $path,
                        'original_name' => mb_substr($upload->getClientOriginalName(), 0, 255),
                        'mime_type' => $upload->getMimeType(),
                        'size' => $upload->getSize(),
                        'checksum' => hash_file('sha256', $upload->getRealPath()),
                        'uploaded_by' => $actor->id,
                    ]);

                    TaskAttachment::create([
                        'weekly_task_id' => $task->id,
                        'file_id' => $file->id,
                        'uploaded_by' => $actor->id,
                    ]);
                }

                ActivityLog::record(
                    'lampiran.tambah',
                    'Melampirkan '.count($files)." berkas pada task \"{$task->title}\"",
                    $task
                );
            });
        } catch (Throwable $e) {
            // Database gagal: jangan tinggalkan berkas yatim di penyimpanan.
            Storage::disk(self::DISK)->delete($stored);

            throw $e;
        }
    }
}

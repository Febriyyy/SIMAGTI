<?php

namespace App\Actions\Tasks;

use App\Models\ActivityLog;
use App\Models\StoredFile;
use App\Models\TaskEvidence;
use App\Models\User;
use App\Models\WeeklyTask;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Mahasiswa mengirim bukti pengerjaan: link (GitHub, Drive, ...) dan/atau berkas (foto, PDF, ZIP, ...).
 * Berkas disimpan di disk privat; hanya bisa diambil lewat aplikasi oleh yang berhak melihat task.
 */
class SubmitTaskEvidence
{
    public const DISK = 'local';

    /**
     * @param  list<UploadedFile>  $files
     * @return list<TaskEvidence>
     */
    public function handle(User $actor, WeeklyTask $task, ?string $url, ?string $title = null, array $files = []): array
    {
        $existingFiles = $task->evidences()->where('type', 'file')->count();
        if ($existingFiles + count($files) > TaskEvidence::MAX_FILES_PER_TASK) {
            throw ValidationException::withMessages([
                'berkas' => 'Satu task maksimal '.TaskEvidence::MAX_FILES_PER_TASK.' berkas bukti. Hapus yang tidak perlu dulu.',
            ]);
        }

        $stored = [];

        try {
            return DB::transaction(function () use ($actor, $task, $url, $title, $files, &$stored) {
                $created = [];

                if ($url) {
                    $created[] = TaskEvidence::create([
                        'weekly_task_id' => $task->id,
                        'submitted_by' => $actor->id,
                        'type' => 'link',
                        'title' => $title,
                        'url' => $url,
                    ]);
                }

                foreach ($files as $upload) {
                    $path = $upload->store("bukti/{$task->internship_id}/{$task->id}", self::DISK);
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

                    $created[] = TaskEvidence::create([
                        'weekly_task_id' => $task->id,
                        'submitted_by' => $actor->id,
                        'type' => 'file',
                        'file_id' => $file->id,
                    ]);
                }

                $parts = array_filter([
                    $url ? 'link' : null,
                    $files ? count($files).' berkas' : null,
                ]);
                ActivityLog::record('bukti.kirim', 'Mengirim bukti ('.implode(' dan ', $parts).") untuk task \"{$task->title}\"", $task);

                return $created;
            });
        } catch (Throwable $e) {
            // Database gagal: jangan tinggalkan berkas yatim di penyimpanan.
            Storage::disk(self::DISK)->delete($stored);

            throw $e;
        }
    }
}

<?php

namespace App\Actions\Activities;

use App\Models\ActivityLog;
use App\Models\Internship;
use App\Models\StoredFile;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

/** Mencatat atau mengubah satu baris timesheet; foto dokumentasi disimpan di disk privat. */
class LogTime
{
    public const DISK = 'local';

    public function create(User $student, Internship $internship, array $data, ?UploadedFile $photo = null): TimeEntry
    {
        return $this->withPhoto($student, $internship, $photo, function (?StoredFile $file) use ($student, $internship, $data) {
            $entry = $internship->timeEntries()->create([
                'weekly_task_id' => $data['weekly_task_id'] ?? null,
                'work_date' => $data['work_date'],
                'hours' => $data['hours'],
                'description' => trim($data['description']),
                'file_id' => $file?->id,
                'created_by' => $student->id,
            ]);

            ActivityLog::record('timesheet.catat', 'Mencatat '.rtrim(rtrim(number_format((float) $data['hours'], 2, '.', ''), '0'), '.')." jam pada {$entry->work_date->toDateString()}", $entry);

            return $entry;
        });
    }

    public function update(User $student, TimeEntry $entry, array $data, ?UploadedFile $photo = null): TimeEntry
    {
        return $this->withPhoto($student, $entry->internship, $photo, function (?StoredFile $file) use ($entry, $data) {
            $old = $entry->photo;
            $entry->update(array_intersect_key($data, array_flip(['work_date', 'hours', 'description', 'weekly_task_id'])) + ($file ? ['file_id' => $file->id] : []));

            if ($file && $old) {
                $old->forceDelete();
                Storage::disk($old->disk)->delete($old->path);
            }

            return $entry;
        });
    }

    public function delete(TimeEntry $entry): void
    {
        $photo = $entry->photo;

        DB::transaction(function () use ($entry, $photo) {
            $entry->delete();
            $photo?->forceDelete();
        });

        if ($photo) {
            Storage::disk($photo->disk)->delete($photo->path);
        }
    }

    private function withPhoto(User $student, Internship $internship, ?UploadedFile $photo, callable $callback): TimeEntry
    {
        $path = null;

        try {
            return DB::transaction(function () use ($student, $internship, $photo, $callback, &$path) {
                $file = null;

                if ($photo) {
                    $path = $photo->store("timesheet/{$internship->id}", self::DISK);
                    $file = StoredFile::create([
                        'disk' => self::DISK,
                        'path' => $path,
                        'original_name' => mb_substr($photo->getClientOriginalName(), 0, 255),
                        'mime_type' => $photo->getMimeType(),
                        'size' => $photo->getSize(),
                        'checksum' => hash_file('sha256', $photo->getRealPath()),
                        'uploaded_by' => $student->id,
                    ]);
                }

                return $callback($file);
            });
        } catch (Throwable $e) {
            if ($path) {
                Storage::disk(self::DISK)->delete($path);
            }

            throw $e;
        }
    }
}

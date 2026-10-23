<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/** Satu baris timesheet/logbook: tanggal, kegiatan, durasi jam, foto dokumentasi (opsional). */
class TimeEntry extends Model
{
    /** Batas satu baris supaya salah ketik (misalnya 80 jam) tertahan. */
    public const MAX_HOURS = 16;

    protected $fillable = [
        'internship_id',
        'weekly_task_id',
        'work_date',
        'hours',
        'description',
        'file_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'work_date' => 'date',
            'hours' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (TimeEntry $entry) {
            if ($entry->internship?->isLocked()) {
                throw new LogicException('Penempatan ini sudah selesai dan dikunci.');
            }
        });
    }

    public function internship(): BelongsTo
    {
        return $this->belongsTo(Internship::class);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(WeeklyTask::class, 'weekly_task_id');
    }

    public function photo(): BelongsTo
    {
        return $this->belongsTo(StoredFile::class, 'file_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

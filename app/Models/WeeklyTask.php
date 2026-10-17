<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use LogicException;

class WeeklyTask extends Model
{
    use SoftDeletes;

    public const DAYS = [
        'senin' => 'Senin',
        'selasa' => 'Selasa',
        'rabu' => 'Rabu',
        'kamis' => 'Kamis',
        'jumat' => 'Jumat',
    ];

    /** status => [label, warna chip] */
    public const STATUSES = [
        'todo' => ['To Do', 'gray'],
        'in_progress' => ['In Progress', 'blue'],
        'completed' => ['Completed', 'green'],
    ];

    protected $fillable = [
        'internship_id',
        'week_number',
        'day',
        'title',
        'description',
        'status',
        'completed_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (WeeklyTask $task) {
            // Penempatan yang sudah selesai dikunci: task tidak bisa diubah lagi.
            if ($task->internship_id && $task->internship?->isLocked()) {
                throw new LogicException('Penempatan ini sudah selesai dan dikunci.');
            }

            if ($task->isDirty('status')) {
                $task->completed_at = $task->status === 'completed' ? now() : null;
            }
        });
    }

    public function internship(): BelongsTo
    {
        return $this->belongsTo(Internship::class);
    }

    public function evidences(): HasMany
    {
        return $this->hasMany(TaskEvidence::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TaskComment::class);
    }

    /** Materi dari mentor untuk task ini. */
    public function attachments(): HasMany
    {
        return $this->hasMany(TaskAttachment::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function dayLabel(): string
    {
        return self::DAYS[$this->day] ?? ucfirst($this->day);
    }

    public function dayShort(): string
    {
        return mb_substr($this->dayLabel(), 0, 3);
    }

    public function dayOrder(): int
    {
        return (int) array_search($this->day, array_keys(self::DAYS), true);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status][0] ?? $this->status;
    }

    public function statusTone(): string
    {
        return self::STATUSES[$this->status][1] ?? 'gray';
    }
}

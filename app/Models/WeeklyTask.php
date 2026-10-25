<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use LogicException;

/**
 * Task kegiatan mahasiswa (nama kelas dipertahankan dari versi awal: dulu task mingguan dari mentor).
 * Sejak v2 task dibuat mahasiswa sendiri, berada di satu kolom board, opsional di satu proyek,
 * dan bisa dikaitkan ke satu tujuan silabus.
 */
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
        'completed' => ['Selesai', 'green'],
    ];

    /** prioritas => [label, warna chip, urutan] */
    public const PRIORITIES = [
        'urgent' => ['Urgent', 'red', 0],
        'high' => ['Tinggi', 'amber', 1],
        'medium' => ['Sedang', 'blue', 2],
        'low' => ['Rendah', 'gray', 3],
    ];

    protected $fillable = [
        'internship_id',
        'project_id',
        'board_column_id',
        'syllabus_item_id',
        'number',
        'week_number',
        'day',
        'title',
        'description',
        'status',
        'priority',
        'start_date',
        'due_date',
        'estimate_hours',
        'labels',
        'position',
        'completed_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
            'start_date' => 'date',
            'due_date' => 'date',
            'estimate_hours' => 'decimal:2',
            'labels' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (WeeklyTask $task) {
            // Penempatan yang sudah selesai dikunci: task tidak bisa diubah lagi.
            if ($task->internship_id && $task->internship?->isLocked()) {
                throw new LogicException('Penempatan ini sudah selesai dan dikunci.');
            }

            // Status mengikuti kategori kolom board tempat task berada.
            if ($task->isDirty('board_column_id') && $task->board_column_id) {
                $task->status = BoardColumn::find($task->board_column_id)?->taskStatus() ?? $task->status;
            }

            // Nomor urut per penempatan (dipakai untuk kode seperti PMS-12).
            if ($task->number === null && $task->internship_id) {
                $task->number = (int) static::withTrashed()->where('internship_id', $task->internship_id)->max('number') + 1;
            }

            // Minggu ke- dihitung dari tenggat (atau tanggal dibuat), dipakai filter dan grafik mingguan.
            if ($task->internship && ($task->isDirty('due_date') || $task->week_number === null)) {
                $date = $task->due_date ?? $task->created_at ?? now();
                $task->week_number = $task->internship->weekOf($date);
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

    /**
     * Alias untuk route /tugas/{task}/bukti/{evidence} (scopeBindings): Laravel menganggap
     * "evidence" tidak punya bentuk jamak, jadi mencari relasi bernama evidence().
     */
    public function evidence(): HasMany
    {
        return $this->evidences();
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TaskComment::class);
    }

    /** Lampiran lama dari versi awal (materi dari mentor). Tetap bisa dibuka, tidak bisa ditambah lagi. */
    public function attachments(): HasMany
    {
        return $this->hasMany(TaskAttachment::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function column(): BelongsTo
    {
        return $this->belongsTo(BoardColumn::class, 'board_column_id');
    }

    public function syllabusItem(): BelongsTo
    {
        return $this->belongsTo(SyllabusItem::class);
    }

    public function checklist(): HasMany
    {
        return $this->hasMany(TaskChecklistItem::class)->orderBy('position');
    }

    /** Alias untuk route binding bertingkat /tugas/{task}/checklist/{item}. */
    public function items(): HasMany
    {
        return $this->checklist();
    }

    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class);
    }

    /** Kode task seperti Jira: PMS-12 (proyek) atau KP-12 (tanpa proyek). */
    public function key(): string
    {
        return ($this->project?->key ?: 'KP').'-'.$this->number;
    }

    public function priorityLabel(): string
    {
        return self::PRIORITIES[$this->priority][0] ?? $this->priority;
    }

    public function priorityTone(): string
    {
        return self::PRIORITIES[$this->priority][1] ?? 'gray';
    }

    public function isDone(): bool
    {
        return $this->status === 'completed';
    }

    public function isOverdue(): bool
    {
        return ! $this->isDone() && $this->due_date !== null && $this->due_date->isBefore(today());
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function dayLabel(): string
    {
        if ($this->day === null) {
            return $this->due_date ? $this->due_date->locale('id')->translatedFormat('l') : '–';
        }

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

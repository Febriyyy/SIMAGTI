<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

/** Tujuan (goal) yang ingin dicapai mahasiswa selama KP; task dan rencana kegiatan bisa dikaitkan ke sini. */
class Goal extends Model
{
    protected $fillable = [
        'internship_id',
        'title',
        'description',
        'achieved_at',
        'position',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'achieved_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Goal $goal) {
            if ($goal->internship?->isLocked()) {
                throw new LogicException('Penempatan ini sudah selesai dan dikunci.');
            }
        });
    }

    public function internship(): BelongsTo
    {
        return $this->belongsTo(Internship::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(WeeklyTask::class);
    }

    public function plans(): HasMany
    {
        return $this->hasMany(ActivityPlan::class);
    }

    public function isAchieved(): bool
    {
        return $this->achieved_at !== null;
    }

    /** Persen task terkait yang sudah Done (pakai withCount tasks / completed_tasks_count bila tersedia). */
    public function percent(): int
    {
        $total = (int) ($this->tasks_count ?? $this->tasks()->count());
        $done = (int) ($this->completed_tasks_count ?? $this->tasks()->where('status', 'completed')->count());

        return $total > 0 ? (int) round($done / $total * 100) : 0;
    }

    /**
     * Goal tercapai otomatis bila punya task dan semua task-nya sudah Done; terbuka lagi bila ada task yang belum.
     * Ditulis lewat query agar tidak terhalang kunci penempatan selesai (status goal hanya mengikuti task).
     */
    public static function syncAchievement(?int $goalId): void
    {
        if (! $goalId) {
            return;
        }
        $tasks = WeeklyTask::query()->where('goal_id', $goalId);
        $total = (clone $tasks)->count();
        $open = (clone $tasks)->where('status', '!=', 'completed')->count();
        $achieved = $total > 0 && $open === 0;

        $current = static::query()->whereKey($goalId)->value('achieved_at');
        if ($achieved && $current === null) {
            static::query()->whereKey($goalId)->update(['achieved_at' => (clone $tasks)->max('completed_at') ?? now()]);
        } elseif (! $achieved && $current !== null) {
            static::query()->whereKey($goalId)->update(['achieved_at' => null]);
        }
    }
}

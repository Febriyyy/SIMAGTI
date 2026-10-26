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
}

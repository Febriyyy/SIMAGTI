<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * Rencana kegiatan di timesheet (tampil sebagai Gantt): nama kegiatan dan rentang tanggal, tanpa jam.
 * Mahasiswa menyusun rencana dulu, baru membuat task di bawah rencana tersebut.
 */
class ActivityPlan extends Model
{
    /** Warna batang di Gantt => label. Rencana tanpa warna memakai warna bergiliran sesuai urutan. */
    public const COLORS = [
        'blue' => 'Biru',
        'teal' => 'Toska',
        'green' => 'Hijau',
        'amber' => 'Kuning',
        'purple' => 'Ungu',
        'pink' => 'Merah muda',
        'red' => 'Merah',
    ];

    protected $fillable = [
        'internship_id',
        'goal_id',
        'title',
        'description',
        'color',
        'start_date',
        'end_date',
        'position',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (ActivityPlan $plan) {
            if ($plan->internship?->isLocked()) {
                throw new LogicException('Penempatan ini sudah selesai dan dikunci.');
            }
        });
    }

    public function internship(): BelongsTo
    {
        return $this->belongsTo(Internship::class);
    }

    public function goal(): BelongsTo
    {
        return $this->belongsTo(Goal::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(WeeklyTask::class);
    }

    /** Warna yang dipakai: pilihan mahasiswa, atau bergiliran menurut urutan rencana. */
    public function colorKey(int $index = 0): string
    {
        $keys = array_keys(self::COLORS);

        return array_key_exists((string) $this->color, self::COLORS) ? $this->color : $keys[$index % count($keys)];
    }

    public function days(): int
    {
        return (int) $this->start_date->diffInDays($this->end_date) + 1;
    }

    /** Keadaan rencana terhadap hari ini: upcoming | active | past. */
    public function phase(?CarbonInterface $today = null): string
    {
        $today ??= Carbon::today();

        return match (true) {
            $today->lt($this->start_date) => 'upcoming',
            $today->gt($this->end_date) => 'past',
            default => 'active',
        };
    }

    /** Persentase task selesai (pakai tasks_count / completed_tasks_count bila sudah dimuat). */
    public function percent(): int
    {
        $total = $this->tasks_count ?? $this->tasks()->count();
        $done = $this->completed_tasks_count ?? $this->tasks()->where('status', 'completed')->count();

        return $total > 0 ? (int) round($done / $total * 100) : 0;
    }

    public function rangeLabel(): string
    {
        $from = $this->start_date->locale('id');
        $to = $this->end_date->locale('id');

        return $from->isSameDay($to)
            ? $from->translatedFormat('j M Y')
            : ($from->year === $to->year
                ? $from->translatedFormat('j M').' – '.$to->translatedFormat('j M Y')
                : $from->translatedFormat('j M Y').' – '.$to->translatedFormat('j M Y'));
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Jadwal sidang/presentasi KP. */
class DefenseSchedule extends Model
{
    public const MODES = ['luring' => 'Luring', 'daring' => 'Daring'];

    protected $fillable = ['internship_id', 'scheduled_at', 'mode', 'place', 'examiner_id', 'note', 'created_by'];

    protected function casts(): array
    {
        return ['scheduled_at' => 'datetime'];
    }

    public function internship(): BelongsTo
    {
        return $this->belongsTo(Internship::class);
    }

    public function examiner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'examiner_id');
    }

    public function isOnline(): bool
    {
        return $this->mode === 'daring';
    }

    /** Sidang sudah lewat (boleh diberi nilai). */
    public function hasPassed(): bool
    {
        return $this->scheduled_at->lte(now());
    }

    /** Mis. "Senin, 24 November 2026 · 09.00" */
    public function whenLabel(): string
    {
        return $this->scheduled_at->locale('id')->translatedFormat('l, j F Y').' · '.$this->scheduled_at->format('H.i');
    }
}

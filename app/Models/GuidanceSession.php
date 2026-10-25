<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Satu kali bimbingan dengan dosen pembimbing: dicatat mahasiswa, dikonfirmasi dosen. */
class GuidanceSession extends Model
{
    public const MODES = ['daring' => 'Daring', 'luring' => 'Luring'];

    /** status => [label, warna chip] */
    public const STATUSES = [
        'pending' => ['Menunggu konfirmasi', 'amber'],
        'confirmed' => ['Dikonfirmasi', 'green'],
        'rejected' => ['Ditolak', 'red'],
    ];

    protected $fillable = [
        'internship_id',
        'held_on',
        'mode',
        'topic',
        'notes',
        'status',
        'lecturer_note',
        'decided_by',
        'decided_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'held_on' => 'date',
            'decided_at' => 'datetime',
        ];
    }

    public function internship(): BelongsTo
    {
        return $this->belongsTo(Internship::class);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status][0] ?? $this->status;
    }

    public function statusTone(): string
    {
        return self::STATUSES[$this->status][1] ?? 'gray';
    }

    public function modeLabel(): string
    {
        return self::MODES[$this->mode] ?? $this->mode;
    }
}

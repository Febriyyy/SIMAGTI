<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Satu kali bimbingan dengan dosen pembimbing: dicatat mahasiswa lalu dikonfirmasi dosen,
 * atau dijadwalkan dosen (jam dan tautan rapat daring) lalu ditandai selesai setelah berlangsung.
 */
class GuidanceSession extends Model
{
    public const MODES = ['daring' => 'Daring', 'luring' => 'Luring'];

    /** status => [label, warna chip] */
    public const STATUSES = [
        'scheduled' => ['Terjadwal', 'blue'],
        'pending' => ['Menunggu konfirmasi', 'amber'],
        'confirmed' => ['Dikonfirmasi', 'green'],
        'rejected' => ['Ditolak', 'red'],
    ];

    protected $fillable = [
        'internship_id',
        'held_on',
        'start_time',
        'mode',
        'meeting_link',
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

    /** Tanggal dan jam mulai (jam 00.00 bila tidak diisi). */
    public function startsAt(): Carbon
    {
        return $this->start_time ? $this->held_on->copy()->setTimeFromTimeString($this->start_time) : $this->held_on->copy();
    }

    public function timeLabel(): ?string
    {
        return $this->start_time ? substr($this->start_time, 0, 5).' WIB' : null;
    }

    /** Jadwal dari dosen yang belum lewat dan punya tautan rapat: mahasiswa bisa bergabung. */
    public function canJoin(): bool
    {
        return $this->status === 'scheduled' && filled($this->meeting_link) && $this->held_on->gte(today());
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

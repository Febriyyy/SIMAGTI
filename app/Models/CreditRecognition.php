<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Usulan rekognisi SKS MBKM: mata kuliah yang disetarakan, diajukan mahasiswa, disetujui kaprodi. */
class CreditRecognition extends Model
{
    /** Batas SKS yang bisa direkognisi dari satu semester magang MBKM. */
    public const MAX_CREDITS = 20;

    /** status => [label, warna chip] */
    public const STATUSES = [
        'diajukan' => ['Menunggu kaprodi', 'amber'],
        'disetujui' => ['Disetujui', 'green'],
        'ditolak' => ['Ditolak', 'red'],
    ];

    protected $fillable = [
        'internship_id',
        'courses',
        'status',
        'note',
        'decided_by',
        'decided_at',
    ];

    protected function casts(): array
    {
        return [
            'courses' => 'array',
            'decided_at' => 'datetime',
        ];
    }

    public function internship(): BelongsTo
    {
        return $this->belongsTo(Internship::class);
    }

    public function totalCredits(): int
    {
        return (int) collect($this->courses)->sum('credits');
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

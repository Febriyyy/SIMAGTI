<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConversionCandidate extends Model
{
    /** status => [label, warna chip] */
    public const STATUSES = [
        'direkomendasikan' => ['Direkomendasikan', 'blue'],
        'ditawari' => ['Ditawari kerja', 'amber'],
        'diterima' => ['Diterima', 'green'],
        'ditolak' => ['Menolak', 'gray'],
    ];

    protected $fillable = [
        'internship_id',
        'status',
        'offered_position',
        'recommended_by',
        'note',
    ];

    public function internship(): BelongsTo
    {
        return $this->belongsTo(Internship::class);
    }

    public function recommender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recommended_by');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(ConversionHistory::class);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status][0] ?? $this->status;
    }

    public function statusTone(): string
    {
        return self::STATUSES[$this->status][1] ?? 'gray';
    }

    /** Ubah status sekaligus mencatat riwayatnya. */
    public function moveTo(string $status, ?int $userId = null, ?string $note = null): void
    {
        abort_unless(array_key_exists($status, self::STATUSES), 422, 'Status konversi tidak dikenal.');

        $from = $this->status;
        $this->update(['status' => $status]);

        $this->histories()->create([
            'from_status' => $from,
            'to_status' => $status,
            'changed_by' => $userId,
            'note' => $note,
        ]);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InternshipApproval extends Model
{
    public const STEP_VERIFY = 'tu_verification';

    public const STEP_KAPRODI = 'kaprodi_approval';

    /** status => [label, warna chip] */
    public const STATUSES = [
        'pending' => ['Menunggu', 'amber'],
        'approved' => ['Disetujui', 'green'],
        'rejected' => ['Ditolak', 'red'],
    ];

    protected $fillable = [
        'internship_id',
        'step',
        'status',
        'reviewed_by',
        'reviewed_at',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
        ];
    }

    public function internship(): BelongsTo
    {
        return $this->belongsTo(Internship::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConversionHistory extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'conversion_candidate_id',
        'from_status',
        'to_status',
        'changed_by',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(ConversionCandidate::class, 'conversion_candidate_id');
    }

    public function changer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}

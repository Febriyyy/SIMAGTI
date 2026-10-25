<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GradeScale extends Model
{
    protected $fillable = [
        'version',
        'grade',
        'min_score',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'min_score' => 'decimal:2',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

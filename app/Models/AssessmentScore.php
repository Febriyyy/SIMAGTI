<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssessmentScore extends Model
{
    protected $fillable = [
        'assessment_id',
        'rubric_aspect_id',
        'score',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'decimal:2',
        ];
    }

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class);
    }

    public function aspect(): BelongsTo
    {
        return $this->belongsTo(RubricAspect::class, 'rubric_aspect_id');
    }

    protected static function booted(): void
    {
        static::saving(function (AssessmentScore $score) {
            if ($score->assessment && $score->assessment->isValidated()) {
                throw new \LogicException('Penilaian yang sudah divalidasi tidak bisa diubah.');
            }
        });
    }
}

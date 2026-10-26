<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssessmentRubric extends Model
{
    protected $fillable = [
        'name',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function aspects(): HasMany
    {
        return $this->hasMany(RubricAspect::class, 'rubric_id');
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(Assessment::class, 'rubric_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Standar penilaian kampus yang berlaku. */
    public static function active(): ?self
    {
        return static::where('is_active', true)->latest('id')->first();
    }

    /** Pertanyaan bernilai (yang dihitung ke nilai akhir). */
    public function scoreAspects(): HasMany
    {
        return $this->aspects()->where('kind', 'score');
    }
}

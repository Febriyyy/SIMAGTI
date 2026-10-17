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

    public static function active(): ?self
    {
        return static::where('is_active', true)->latest('id')->first();
    }

    /** Total bobot semua aspek; harus 100 agar nilai akhir masuk akal. */
    public function totalWeight(): float
    {
        return (float) $this->aspects()->sum('weight');
    }
}

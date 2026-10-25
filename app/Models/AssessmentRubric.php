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
        'owner_id',
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

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /** Rubrik kampus yang berlaku (bukan milik dosen). */
    public static function active(): ?self
    {
        return static::where('is_active', true)->whereNull('owner_id')->latest('id')->first();
    }

    /** Aspek bernilai (yang dihitung ke nilai akhir); pertanyaan isian tidak ikut. */
    public function scoreAspects(): HasMany
    {
        return $this->aspects()->where('kind', 'score');
    }

    /** Total bobot semua aspek; harus 100 agar nilai akhir masuk akal. */
    public function totalWeight(): float
    {
        return (float) $this->scoreAspects()->sum('weight');
    }
}

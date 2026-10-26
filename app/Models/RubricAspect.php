<?php

namespace App\Models;

use App\Support\AssessmentStandard;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RubricAspect extends Model
{
    protected $fillable = [
        'rubric_id',
        'category',
        'name',
        'description',
        'kind',
        'weight',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'weight' => 'decimal:2',
        ];
    }

    public function rubric(): BelongsTo
    {
        return $this->belongsTo(AssessmentRubric::class, 'rubric_id');
    }

    public function scores(): HasMany
    {
        return $this->hasMany(AssessmentScore::class);
    }

    public function categoryLabel(): string
    {
        return AssessmentStandard::categoryLabel($this->category);
    }

    public function isText(): bool
    {
        return $this->kind === 'text';
    }
}

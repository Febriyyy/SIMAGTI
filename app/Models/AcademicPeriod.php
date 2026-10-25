<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicPeriod extends Model
{
    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function internships(): HasMany
    {
        return $this->hasMany(Internship::class);
    }

    public static function current(): ?self
    {
        return static::where('is_active', true)->latest('start_date')->first();
    }
}

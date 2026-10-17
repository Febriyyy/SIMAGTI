<?php

namespace App\Models;

use App\Support\Grading;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DefenseScore extends Model
{
    protected $fillable = [
        'internship_id',
        'score',
        'letter_grade',
        'grade_scale_version',
        'defended_at',
        'entered_by',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'decimal:2',
            'defended_at' => 'date',
        ];
    }

    public function internship(): BelongsTo
    {
        return $this->belongsTo(Internship::class);
    }

    public function enteredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'entered_by');
    }

    protected static function booted(): void
    {
        // Huruf mutu ditetapkan saat nilai pertama kali disimpan dan tidak berubah mengikuti skala baru.
        static::saving(function (DefenseScore $defense) {
            if ($defense->letter_grade === null) {
                $version = Grading::currentVersion();
                $defense->grade_scale_version = $version;
                $defense->letter_grade = Grading::letterFor((float) $defense->score, $version);
            }
        });
    }
}

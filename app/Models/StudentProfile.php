<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentProfile extends Model
{
    protected $fillable = [
        'user_id',
        'study_program_id',
        'entry_year',
        'gpa',
        'bio',
    ];

    protected function casts(): array
    {
        return [
            'gpa' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function studyProgram(): BelongsTo
    {
        return $this->belongsTo(StudyProgram::class);
    }

    /**
     * Semester berjalan dihitung dari angkatan. Tahun akademik dimulai September (semester ganjil):
     * angkatan 2023 → Oktober 2026 = semester 7.
     */
    public function semester(?\DateTimeInterface $at = null): ?int
    {
        if (! $this->entry_year) {
            return null;
        }

        $at ??= now();
        $year = (int) $at->format('Y');
        $month = (int) $at->format('n');

        return max(1, ($year - (int) $this->entry_year) * 2 + ($month >= 9 ? 1 : 0));
    }
}

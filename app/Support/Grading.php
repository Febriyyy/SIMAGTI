<?php

namespace App\Support;

use App\Models\GradeScale;
use App\Models\Setting;

class Grading
{
    /** Skala awal (batas bawah). Kaprodi bisa menggantinya dari pengaturan. */
    public const DEFAULT_SCALE = [
        'A' => 88,
        'B+' => 82,
        'B' => 70,
        'C+' => 64,
        'C' => 56,
        'D' => 40,
        'E' => 0,
    ];

    /** Versi skala yang sedang berlaku. */
    public static function currentVersion(): int
    {
        $version = (int) Setting::get('grade_scale_version', 0);

        return $version > 0 ? $version : (int) (GradeScale::max('version') ?? 1);
    }

    /** Buat versi skala baru. Nilai yang sudah divalidasi tetap memakai versi lamanya. */
    public static function publishScale(array $scale, ?int $userId = null): int
    {
        $version = ((int) GradeScale::max('version')) + 1;

        foreach ($scale as $grade => $min) {
            GradeScale::create([
                'version' => $version,
                'grade' => $grade,
                'min_score' => $min,
                'created_by' => $userId,
            ]);
        }

        Setting::put('grade_scale_version', $version, 'Versi skala nilai huruf yang berlaku', $userId);

        return $version;
    }

    public static function letterFor(float $score, ?int $version = null): string
    {
        $version ??= self::currentVersion();

        $row = GradeScale::where('version', $version)
            ->where('min_score', '<=', $score)
            ->orderByDesc('min_score')
            ->first();

        return $row?->grade ?? 'E';
    }
}

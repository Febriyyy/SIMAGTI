<?php

namespace App\Support;

use App\Models\GradeScale;
use App\Models\Setting;

class Grading
{
    /** Skala huruf mutu standar kampus (batas bawah). Admin bisa menggantinya di Standar Penilaian. */
    public const DEFAULT_SCALE = [
        'A' => 80,
        'B+' => 74,
        'B' => 68,
        'C+' => 62,
        'C' => 56,
        'D' => 45,
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

    /** Skala yang berlaku: huruf => batas bawah, dari tertinggi. @return array<string, float> */
    public static function scale(?int $version = null): array
    {
        $rows = GradeScale::where('version', $version ?? self::currentVersion())->orderByDesc('min_score')->get();

        return $rows->isEmpty()
            ? array_map('floatval', self::DEFAULT_SCALE)
            : $rows->mapWithKeys(fn ($r) => [$r->grade => (float) $r->min_score])->all();
    }

    /** Huruf-huruf pada skala yang berlaku, dari tertinggi ke terendah. */
    public static function letters(?int $version = null): array
    {
        $letters = GradeScale::where('version', $version ?? self::currentVersion())
            ->orderByDesc('min_score')
            ->pluck('grade')
            ->all();

        return $letters !== [] ? $letters : array_keys(self::DEFAULT_SCALE);
    }

    /**
     * Jumlah per huruf mutu, urut dari tertinggi. Huruf yang tidak ada di skala berlaku tetap dihitung di akhir.
     *
     * @param  iterable<string|null>  $grades
     * @return array<string, int>
     */
    public static function distribution(iterable $grades): array
    {
        $counts = array_fill_keys(self::letters(), 0);

        foreach ($grades as $grade) {
            if ($grade !== null && $grade !== '') {
                $counts[$grade] = ($counts[$grade] ?? 0) + 1;
            }
        }

        return $counts;
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

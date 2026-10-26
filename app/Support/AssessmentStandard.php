<?php

namespace App\Support;

use App\Models\Assessment;
use App\Models\AssessmentRubric;
use App\Models\Setting;
use Illuminate\Support\Collection;

/**
 * Standar penilaian akhir kampus.
 * - Admin: pertanyaan per kategori (hard skill, soft skill, attitude), skala nilai, huruf mutu.
 * - Kaprodi: bobot tiap kategori dalam persen (total 100).
 * - Company Supervisor: mengklik satu skala untuk setiap pertanyaan.
 *
 * Nilai kategori = rata-rata nilai pertanyaannya (0–100). Nilai akhir = jumlah (nilai kategori × bobot / 100).
 */
class AssessmentStandard
{
    /** kategori => [label, keterangan singkat] */
    public const CATEGORIES = [
        'hard' => ['Hard skill', 'Kemampuan teknis dan hasil kerja'],
        'soft' => ['Soft skill', 'Komunikasi, kerja sama, dan inisiatif'],
        'attitude' => ['Attitude', 'Sikap, disiplin, dan tanggung jawab'],
    ];

    /** Pertanyaan bawaan standar kampus (bisa diubah admin). */
    public const DEFAULT_QUESTIONS = [
        'hard' => ['Kemampuan kerja', 'Keterampilan kerja', 'Kualitas hasil kerja', 'Kreativitas dan inovasi'],
        'soft' => ['Kemampuan berkomunikasi', 'Kerja sama', 'Kerajinan dan inisiatif', 'Percaya diri'],
        'attitude' => ['Ketepatan waktu dan disiplin', 'Sikap dan prosedur kerja', 'Tanggung jawab', 'Kehadiran', 'Mematuhi aturan perusahaan', 'Penampilan'],
    ];

    public const WEIGHTS_KEY = 'simagti.assessment.weights';

    public const SCALE_KEY = 'simagti.assessment.scale';

    public static function categoryLabel(string $category): string
    {
        return self::CATEGORIES[$category][0] ?? ucfirst($category);
    }

    /** Bobot kategori (persen) yang berlaku. @return array<string, float> */
    public static function weights(): array
    {
        $saved = (array) Campus::get('assessment.weights');
        $weights = [];
        foreach (array_keys(self::CATEGORIES) as $key) {
            $weights[$key] = (float) ($saved[$key] ?? 0);
        }

        return $weights;
    }

    /** @param  array<string, float|int>  $weights */
    public static function saveWeights(array $weights, ?int $userId = null): void
    {
        Setting::put(self::WEIGHTS_KEY, array_map('floatval', array_intersect_key($weights, self::CATEGORIES)), 'Bobot kategori penilaian akhir (kaprodi)', $userId);
        Campus::flush();
    }

    /** Skala nilai, dari tertinggi. @return list<array{label: string, value: float}> */
    public static function scale(): array
    {
        return collect((array) Campus::get('assessment.scale'))
            ->map(fn ($row) => ['label' => (string) $row['label'], 'value' => (float) $row['value']])
            ->sortByDesc('value')
            ->values()
            ->all();
    }

    /** @param  list<array{label: string, value: float|int}>  $scale */
    public static function saveScale(array $scale, ?int $userId = null): void
    {
        Setting::put(self::SCALE_KEY, array_values($scale), 'Skala nilai penilaian akhir (admin)', $userId);
        Campus::flush();
    }

    /** Label skala untuk satu nilai (null bila nilai tidak ada di skala yang berlaku). */
    public static function scaleLabel(?float $value): ?string
    {
        if ($value === null) {
            return null;
        }

        foreach (self::scale() as $row) {
            if (abs($row['value'] - $value) < 0.001) {
                return $row['label'];
            }
        }

        return null;
    }

    /** Rubrik kampus yang berlaku; dibuat dari pertanyaan bawaan bila belum ada. */
    public static function rubric(): AssessmentRubric
    {
        $rubric = AssessmentRubric::active() ?? self::createDefaultRubric();

        return $rubric->load(['aspects' => fn ($q) => $q->orderBy('sort_order')->orderBy('id')]);
    }

    public static function createDefaultRubric(?int $userId = null): AssessmentRubric
    {
        $rubric = AssessmentRubric::create(['name' => 'Standar Penilaian KP', 'is_active' => true, 'created_by' => $userId]);

        $order = 0;
        foreach (self::DEFAULT_QUESTIONS as $category => $questions) {
            foreach ($questions as $name) {
                $rubric->aspects()->create(['category' => $category, 'name' => $name, 'kind' => 'score', 'weight' => 0, 'sort_order' => ++$order]);
            }
        }

        return $rubric;
    }

    /**
     * Rincian nilai satu penilaian: per kategori rata-rata, bobot, kontribusi; dan nilai akhir.
     *
     * @return array{categories: array<string, array{label: string, weight: float, average: ?float, contribution: float, answered: int, total: int}>, final: float, complete: bool}
     */
    public static function breakdown(AssessmentRubric $rubric, Collection $scores, ?array $weights = null): array
    {
        $weights ??= self::weights();
        $scores = $scores->keyBy('rubric_aspect_id');
        $categories = [];
        $final = 0.0;
        $complete = true;

        foreach (self::CATEGORIES as $key => [$label]) {
            $aspects = $rubric->aspects->where('category', $key)->where('kind', '!=', 'text');
            $values = $aspects->map(fn ($a) => $scores->get($a->id)?->score)->filter(fn ($v) => $v !== null)->map(fn ($v) => (float) $v);
            $average = $values->isEmpty() ? null : round($values->avg(), 2);
            $weight = (float) ($weights[$key] ?? 0);
            $contribution = $average === null ? 0.0 : round($average * $weight / 100, 2);

            if ($values->count() < $aspects->count()) {
                $complete = false;
            }

            $final += $contribution;
            $categories[$key] = [
                'label' => $label,
                'weight' => $weight,
                'average' => $average,
                'contribution' => $contribution,
                'answered' => $values->count(),
                'total' => $aspects->count(),
            ];
        }

        return ['categories' => $categories, 'final' => round($final, 2), 'complete' => $complete];
    }

    public static function totalWeight(?array $weights = null): float
    {
        return array_sum($weights ?? self::weights());
    }

    /** Bobot yang dipakai satu penilaian: tersimpan di penilaian (setelah dikirim) atau yang berlaku sekarang. */
    public static function weightsFor(?Assessment $assessment): array
    {
        return $assessment?->category_weights ?: self::weights();
    }
}

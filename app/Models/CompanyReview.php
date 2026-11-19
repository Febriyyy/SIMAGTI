<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

/** Ulasan tempat magang dari mahasiswa: tiga aspek 1–5, rekomendasi, komentar. */
class CompanyReview extends Model
{
    /** kolom => [label, keterangan singkat] */
    public const ASPECTS = [
        'mentoring' => ['Bimbingan supervisor', 'Arahan dan dukungan selama magang'],
        'work_fit' => ['Kesesuaian tugas', 'Tugas sesuai dengan bidang studi'],
        'environment' => ['Lingkungan kerja', 'Suasana tim dan fasilitas'],
    ];

    protected $fillable = ['internship_id', 'company_id', 'student_id', 'mentoring', 'work_fit', 'environment', 'rating', 'recommend', 'comment'];

    protected function casts(): array
    {
        return ['rating' => 'decimal:2', 'recommend' => 'boolean'];
    }

    public function internship(): BelongsTo
    {
        return $this->belongsTo(Internship::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * Ringkasan ulasan satu perusahaan.
     *
     * @param  Collection<int, CompanyReview>  $reviews
     * @return array{count: int, rating: ?float, recommend: ?int, aspects: array<string, ?float>}
     */
    public static function summarize(Collection $reviews): array
    {
        $count = $reviews->count();

        return [
            'count' => $count,
            'rating' => $count ? round((float) $reviews->avg('rating'), 1) : null,
            'recommend' => $count ? (int) round($reviews->where('recommend', true)->count() / $count * 100) : null,
            'aspects' => collect(self::ASPECTS)->mapWithKeys(fn ($a, $key) => [$key => $count ? round((float) $reviews->avg($key), 1) : null])->all(),
        ];
    }
}

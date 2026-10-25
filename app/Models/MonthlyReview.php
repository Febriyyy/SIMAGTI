<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Satu bulan KP: refleksi mahasiswa, penilaian singkat Company Supervisor (seminar: dosen pembimbing)
 * dengan skala 1-4 untuk hard skill, soft skill, dan attitude, lalu catatan dosen pembimbing.
 * Timesheet tidak perlu disetujui supervisor (supaya pihak perusahaan tidak terbebani).
 */
class MonthlyReview extends Model
{
    /** Skala nilai bulanan => label */
    public const SCALE = [
        1 => 'Perlu bimbingan',
        2 => 'Cukup',
        3 => 'Baik',
        4 => 'Sangat baik',
    ];

    /** Kategori nilai => [kolom, label] (mengikuti kelompok tujuan silabus) */
    public const CATEGORIES = [
        'hard' => ['score_hard', 'Hard skill'],
        'soft' => ['score_soft', 'Soft skill'],
        'attitude' => ['score_attitude', 'Attitude'],
    ];

    protected $fillable = [
        'internship_id',
        'month_number',
        'period_start',
        'period_end',
        'learned',
        'obstacles',
        'plan',
        'syllabus_item_ids',
        'reflected_at',
        'score_hard',
        'score_soft',
        'score_attitude',
        'reviewer_note',
        'reviewed_by',
        'reviewed_at',
        'lecturer_note',
        'lecturer_noted_by',
        'lecturer_noted_at',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'syllabus_item_ids' => 'array',
            'reflected_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'lecturer_noted_at' => 'datetime',
        ];
    }

    public function internship(): BelongsTo
    {
        return $this->belongsTo(Internship::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function lecturer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lecturer_noted_by');
    }

    /** Ambil atau buat baris untuk bulan KP ke-n. */
    public static function forMonth(Internship $internship, int $month): self
    {
        [$start, $end] = $internship->monthRange($month);

        return static::firstOrCreate(
            ['internship_id' => $internship->id, 'month_number' => $month],
            ['period_start' => $start->toDateString(), 'period_end' => $end->toDateString()]
        );
    }

    public function isReflected(): bool
    {
        return $this->reflected_at !== null;
    }

    public function isReviewed(): bool
    {
        return $this->reviewed_at !== null;
    }

    public function hasEnded(): bool
    {
        return $this->period_end->copy()->endOfDay()->isPast();
    }

    /** Refleksi bisa diisi mulai satu minggu sebelum bulan berakhir. */
    public function isOpenForReflection(): bool
    {
        return Carbon::today()->gte($this->period_end->copy()->subDays(7));
    }

    /** Penilai boleh menilai setelah refleksi masuk, atau setelah bulan berakhir walau mahasiswa belum mengisi. */
    public function isOpenForReview(): bool
    {
        return $this->isReflected() || $this->hasEnded();
    }

    public function averageScore(): ?float
    {
        $scores = array_filter([$this->score_hard, $this->score_soft, $this->score_attitude], fn ($v) => $v !== null);

        return $scores ? round(array_sum($scores) / count($scores), 2) : null;
    }

    public function label(): string
    {
        return 'Bulan ke-'.$this->month_number;
    }

    public function rangeLabel(): string
    {
        return $this->period_start->locale('id')->translatedFormat('j M').' – '.$this->period_end->locale('id')->translatedFormat('j M Y');
    }

    public function statusLabel(): string
    {
        return match (true) {
            $this->lecturer_noted_at !== null => 'Selesai',
            $this->isReviewed() => 'Dinilai',
            $this->isReflected() => 'Menunggu penilaian',
            $this->hasEnded() => 'Refleksi belum diisi',
            default => 'Berjalan',
        };
    }

    public function statusTone(): string
    {
        return match (true) {
            $this->lecturer_noted_at !== null, $this->isReviewed() => 'green',
            $this->isReflected() => 'amber',
            $this->hasEnded() => 'red',
            default => 'gray',
        };
    }
}

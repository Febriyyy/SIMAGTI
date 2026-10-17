<?php

namespace App\Models;

use App\Support\Grading;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

class Assessment extends Model
{
    public const STATUSES = [
        'draft' => ['Draft', 'gray'],
        'submitted' => ['Menunggu validasi', 'amber'],
        'validated' => ['Tervalidasi', 'green'],
    ];

    protected $fillable = [
        'internship_id',
        'rubric_id',
        'assessor_id',
        'status',
        'final_score',
        'letter_grade',
        'grade_scale_version',
        'submitted_at',
        'validated_by',
        'validated_at',
        'supervisor_note',
    ];

    protected function casts(): array
    {
        return [
            'final_score' => 'decimal:2',
            'submitted_at' => 'datetime',
            'validated_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // Setelah divalidasi, nilai dan huruf mutu terkunci permanen.
        static::updating(function (Assessment $assessment) {
            if ($assessment->getOriginal('status') === 'validated') {
                throw new LogicException('Penilaian yang sudah divalidasi tidak bisa diubah.');
            }
        });
    }

    public function internship(): BelongsTo
    {
        return $this->belongsTo(Internship::class);
    }

    public function rubric(): BelongsTo
    {
        return $this->belongsTo(AssessmentRubric::class, 'rubric_id');
    }

    public function assessor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assessor_id');
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function scores(): HasMany
    {
        return $this->hasMany(AssessmentScore::class);
    }

    public function isValidated(): bool
    {
        return $this->status === 'validated';
    }

    /** Nilai akhir = jumlah (skor aspek x bobot aspek / 100). */
    public function computeFinalScore(): float
    {
        $total = 0.0;

        foreach ($this->scores()->with('aspect')->get() as $score) {
            $total += (float) $score->score * (float) $score->aspect->weight / 100;
        }

        return round($total, 2);
    }

    /** Mentor mengirim penilaian untuk divalidasi ketua divisi. */
    public function submit(): void
    {
        $this->update(['status' => 'submitted', 'submitted_at' => now()]);
    }

    /** Ketua divisi memvalidasi: nilai akhir dan huruf mutu dikunci memakai skala yang berlaku saat ini. */
    public function validateBy(User $user, ?string $note = null): void
    {
        $version = Grading::currentVersion();
        $final = $this->computeFinalScore();

        $this->update([
            'status' => 'validated',
            'final_score' => $final,
            'letter_grade' => Grading::letterFor($final, $version),
            'grade_scale_version' => $version,
            'validated_by' => $user->id,
            'validated_at' => now(),
            'supervisor_note' => $note,
        ]);
    }

    /** Rubrik yang dipakai: rubrik penilaian yang sudah ada, atau rubrik aktif untuk penilaian baru. */
    public static function rubricFor(Internship $internship): ?AssessmentRubric
    {
        $rubric = $internship->assessment?->rubric ?? AssessmentRubric::active();

        return $rubric?->load(['aspects' => fn ($q) => $q->orderBy('sort_order')]);
    }

    /** Draft yang pernah dikirim lalu dikembalikan ketua divisi (ada catatan). */
    public function wasReturned(): bool
    {
        return $this->status === 'draft' && filled($this->supervisor_note);
    }

    /** Huruf mutu: yang terkunci jika sudah divalidasi, atau perkiraan dari skala yang berlaku. */
    public function letter(): ?string
    {
        if ($this->letter_grade) {
            return $this->letter_grade;
        }

        return $this->final_score !== null ? Grading::letterFor((float) $this->final_score) : null;
    }

    public function statusLabel(): string
    {
        return $this->wasReturned() ? 'Dikembalikan' : (self::STATUSES[$this->status][0] ?? $this->status);
    }

    public function statusTone(): string
    {
        return $this->wasReturned() ? 'red' : (self::STATUSES[$this->status][1] ?? 'gray');
    }
}

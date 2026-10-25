<?php

namespace App\Models;

use App\Support\Campus;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class Internship extends Model
{
    use SoftDeletes;

    /** Status yang membuat penempatan dianggap aktif (satu per mahasiswa). */
    public const ACTIVE_STATUSES = ['diajukan', 'berjalan'];

    /** Jalur dan sub-jenis KP: program => [label, [sub-jenis => label]] */
    public const PROGRAMS = [
        'magang' => ['Magang', ['industri' => 'KP Industri', 'mbkm' => 'MBKM Mandiri']],
        'seminar' => ['Seminar', ['pelaksana' => 'Pelaksana Kegiatan Seminar', 'peserta' => 'Peserta Seminar']],
    ];

    /** Ritme kerja mahasiswa (satu periode = satu sprint). */
    public const CADENCES = [
        'daily' => 'Harian',
        'weekly' => 'Mingguan',
        'monthly' => 'Bulanan',
    ];

    /** Status silabus => [label, warna chip] */
    public const SYLLABUS_STATUSES = [
        'draft' => ['Draf', 'gray'],
        'submitted' => ['Menunggu persetujuan', 'amber'],
        'revision' => ['Perlu revisi', 'red'],
        'approved' => ['Disetujui', 'green'],
    ];

    /** status => [label, warna chip] */
    public const STATUSES = [
        'diajukan' => ['Diajukan', 'gray'],
        'berjalan' => ['Berjalan', 'blue'],
        'selesai' => ['Selesai', 'green'],
        'ditolak' => ['Ditolak', 'red'],
        'dibatalkan' => ['Dibatalkan', 'red'],
        'pindah' => ['Pindah tempat', 'gray'],
    ];

    /** Status akhir yang mengunci penempatan (hanya bisa dibaca). */
    public const CLOSED_STATUSES = ['selesai', 'dibatalkan', 'pindah'];

    /** Hasil akhir KP Industri dan Seminar => [label, warna chip] */
    public const RESULTS = [
        'diterima' => ['Diterima', 'green'],
        'tidak' => ['Tidak diterima', 'red'],
    ];

    /** Konfirmasi Company Supervisor => [label, warna chip] */
    public const CONFIRMATIONS = [
        'confirmed' => ['Dikonfirmasi supervisor', 'green'],
        'disputed' => ['Disanggah supervisor', 'red'],
    ];

    protected $fillable = [
        'student_id',
        'company_id',
        'division_id',
        'position',
        'topic',
        'organizer',
        'supervisor_name',
        'supervisor_title',
        'supervisor_email',
        'supervisor_phone',
        'supervisor_confirmation',
        'supervisor_confirmation_note',
        'supervisor_confirmed_at',
        'lecturer_id',
        'assessment_rubric_id',
        'result',
        'result_note',
        'lecturer_score',
        'final_score',
        'final_grade',
        'result_by',
        'result_at',
        'academic_period_id',
        'track',
        'program',
        'program_type',
        'cadence',
        'syllabus_status',
        'syllabus_note',
        'syllabus_reviewed_by',
        'syllabus_reviewed_at',
        'status',
        'start_date',
        'end_date',
        'locked_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'locked_at' => 'datetime',
            'supervisor_confirmed_at' => 'datetime',
            'result_at' => 'datetime',
            'lecturer_score' => 'decimal:2',
            'final_score' => 'decimal:2',
            'syllabus_reviewed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Internship $internship) {
            if ($internship->supervisor_email !== null) {
                $internship->supervisor_email = mb_strtolower(trim($internship->supervisor_email));
            }

            // Menjaga aturan: satu mahasiswa hanya boleh punya satu penempatan aktif.
            $internship->active_student_id = in_array($internship->status, self::ACTIVE_STATUSES, true)
                ? $internship->student_id
                : null;

            // KP selesai, dibatalkan, atau pindah tempat: penempatan dikunci dan hanya bisa dibaca.
            if (in_array($internship->status, self::CLOSED_STATUSES, true) && $internship->locked_at === null) {
                $internship->locked_at = now();
            }
        });

        static::created(function (Internship $internship) {
            $internship->recordStatus(null, $internship->status);
        });

        static::updated(function (Internship $internship) {
            if ($internship->wasChanged('status')) {
                $internship->recordStatus($internship->getOriginal('status'), $internship->status);
            }
        });
    }

    private function recordStatus(?string $from, string $to): void
    {
        InternshipStatusHistory::create([
            'internship_id' => $this->id,
            'from_status' => $from,
            'to_status' => $to,
            'changed_by' => auth()->id(),
        ]);
    }

    public function isLocked(): bool
    {
        return $this->locked_at !== null;
    }

    // ---------- Relasi ----------

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(CompanyDivision::class, 'division_id');
    }

    public function lecturer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lecturer_id');
    }

    public function academicPeriod(): BelongsTo
    {
        return $this->belongsTo(AcademicPeriod::class);
    }

    public function staff(): HasMany
    {
        return $this->hasMany(InternshipStaff::class);
    }

    /** Company Supervisor penempatan ini (gabungan peran mentor dan ketua divisi sejak v2). */
    public function supervisor(): HasOneThrough
    {
        return $this->hasOneThrough(User::class, InternshipStaff::class, 'internship_id', 'id', 'id', 'user_id')
            ->where('internship_staff.role', InternshipStaff::ROLE_SUPERVISOR);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(InternshipApproval::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(InternshipStatusHistory::class);
    }

    public function assessment(): HasOne
    {
        return $this->hasOne(Assessment::class);
    }

    public function defenseScore(): HasOne
    {
        return $this->hasOne(DefenseScore::class);
    }

    public function conversion(): HasOne
    {
        return $this->hasOne(ConversionCandidate::class);
    }

    public function certificate(): HasOne
    {
        return $this->hasOne(Certificate::class);
    }

    /** Pasang Company Supervisor pada penempatan ini. */
    public function assignSupervisor(User|int $user): InternshipStaff
    {
        return $this->staff()->firstOrCreate([
            'user_id' => $user instanceof User ? $user->id : $user,
            'role' => InternshipStaff::ROLE_SUPERVISOR,
        ]);
    }

    public function isSupervisedBy(User|int $user): bool
    {
        return $this->staff()
            ->where('user_id', $user instanceof User ? $user->id : $user)
            ->where('role', InternshipStaff::ROLE_SUPERVISOR)
            ->exists();
    }

    public function monthlyReviews(): HasMany
    {
        return $this->hasMany(MonthlyReview::class)->orderBy('month_number');
    }

    public function guidanceSessions(): HasMany
    {
        return $this->hasMany(GuidanceSession::class)->orderByDesc('held_on');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(InternshipDocument::class);
    }

    public function creditRecognition(): HasOne
    {
        return $this->hasOne(CreditRecognition::class);
    }

    public function changeRequests(): HasMany
    {
        return $this->hasMany(ChangeRequest::class)->latest('id');
    }

    public function rubric(): BelongsTo
    {
        return $this->belongsTo(AssessmentRubric::class, 'assessment_rubric_id');
    }

    public function resultBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'result_by');
    }

    public function document(string $type): ?InternshipDocument
    {
        return $this->relationLoaded('documents')
            ? $this->documents->firstWhere('type', $type)
            : $this->documents()->where('type', $type)->first();
    }

    /** Jumlah bulan KP untuk siklus bulanan (bulan terakhir boleh tidak penuh). */
    public function monthCount(): int
    {
        $n = 1;
        while ($n < 24 && $this->start_date->copy()->addMonthsNoOverflow($n)->lt($this->end_date)) {
            $n++;
        }

        return $n;
    }

    /** Bulan KP ke-n: [mulai, akhir]. Bulan ke-1 dimulai di tanggal mulai KP. */
    public function monthRange(int $month): array
    {
        $start = $this->start_date->copy()->addMonthsNoOverflow($month - 1)->startOfDay();
        $end = $month >= $this->monthCount()
            ? $this->end_date->copy()->endOfDay() // bulan terakhir sampai tanggal selesai KP
            : $this->start_date->copy()->addMonthsNoOverflow($month)->subDay()->endOfDay();

        return [$start, $end];
    }

    /** Jumlah bimbingan yang sudah dikonfirmasi dosen. */
    public function confirmedGuidanceCount(): int
    {
        return $this->guidanceSessions()->where('status', 'confirmed')->count();
    }

    public function resultLabel(): ?string
    {
        if ($this->isMbkm()) {
            return $this->final_grade ? $this->final_grade.' ('.rtrim(rtrim(number_format((float) $this->final_score, 2, ',', ''), '0'), ',').')' : null;
        }

        return self::RESULTS[$this->result][0] ?? null;
    }

    public function resultTone(): string
    {
        return $this->isMbkm() ? 'green' : (self::RESULTS[$this->result][1] ?? 'gray');
    }

    public function isClosed(): bool
    {
        return in_array($this->status, self::CLOSED_STATUSES, true);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(WeeklyTask::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function boardColumns(): HasMany
    {
        return $this->hasMany(BoardColumn::class)->orderBy('position');
    }

    public function syllabusItems(): HasMany
    {
        return $this->hasMany(SyllabusItem::class)->orderBy('position');
    }

    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class);
    }

    /**
     * Kolom board mahasiswa. Dibuat otomatis saat pertama dipakai:
     * To Do, In Progress, Review, Done (mahasiswa boleh menambah, mengganti nama, atau menghapus).
     *
     * @return Collection<int, BoardColumn>
     */
    public function ensureBoard(): Collection
    {
        if (! $this->boardColumns()->exists()) {
            foreach (BoardColumn::DEFAULTS as $i => [$name, $category]) {
                $this->boardColumns()->create(['name' => $name, 'category' => $category, 'position' => $i]);
            }
        }

        return $this->boardColumns()->get();
    }

    public function meetings(): HasMany
    {
        return $this->hasMany(WeeklyMeeting::class);
    }

    /** Meeting untuk minggu berjalan (jika sudah diatur mentor). */
    public function currentMeeting(): ?WeeklyMeeting
    {
        return $this->meetings()->where('week_number', $this->currentWeek())->first();
    }

    // ---------- Scope ----------

    public function scopeSupervisedBy(Builder $query, User|int $user): Builder
    {
        return $query->whereHas('staff', fn ($q) => $q
            ->where('user_id', $user instanceof User ? $user->id : $user)
            ->where('role', InternshipStaff::ROLE_SUPERVISOR));
    }

    /** Hanya penempatan yang boleh dilihat oleh pengguna tertentu. */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return match ($user->role) {
            'mahasiswa' => $query->where('student_id', $user->id),
            'supervisor' => $query->supervisedBy($user),
            'dospem' => $query->where('lecturer_id', $user->id),
            'kaprodi', 'biro', 'tu' => $query, // kaprodi, biro, dan TU fakultas melihat semuanya
            default => $query->whereRaw('1 = 0'),
        };
    }

    /** KP mandiri yang sudah diverifikasi biro dan menunggu persetujuan akhir kaprodi. */
    public function scopeAwaitingKaprodi(Builder $query): Builder
    {
        // Magang menunggu verifikasi biro dulu; seminar (tanpa perusahaan) langsung ke kaprodi.
        return $query->where('track', 'mandiri')
            ->where('status', 'diajukan')
            ->whereDoesntHave('approvals', fn ($q) => $q
                ->where('step', InternshipApproval::STEP_BIRO)
                ->where('status', '!=', 'approved'))
            ->whereHas('approvals', fn ($q) => $q
                ->where('step', InternshipApproval::STEP_KAPRODI)
                ->where('status', 'pending'));
    }

    /** Penempatan magang yang sudah disetujui tetapi belum punya Company Supervisor (antrean TU Fakultas). */
    public function scopeAwaitingSupervisor(Builder $query): Builder
    {
        return $query->where('program', 'magang')
            ->where('status', 'berjalan')
            ->whereNotNull('supervisor_email')
            ->whereDoesntHave('staff', fn ($q) => $q->where('role', InternshipStaff::ROLE_SUPERVISOR));
    }

    /** Tambahkan tasks_count dan completed_tasks_count. */
    public function scopeWithProgress(Builder $query): Builder
    {
        return $query->withCount([
            'tasks',
            'tasks as completed_tasks_count' => fn ($q) => $q->where('status', 'completed'),
        ]);
    }

    // ---------- Waktu ----------

    private static function daysBetween(CarbonInterface $from, CarbonInterface $to): int
    {
        $a = $from->copy()->startOfDay()->getTimestamp();
        $b = $to->copy()->startOfDay()->getTimestamp();

        return (int) round(($b - $a) / 86400);
    }

    public function totalDays(): int
    {
        return max(1, self::daysBetween($this->start_date, $this->end_date) + 1);
    }

    public function totalWeeks(): int
    {
        return max(1, (int) ceil($this->totalDays() / 7));
    }

    public function durationMonths(): int
    {
        return max(1, (int) round($this->totalDays() / 30.4));
    }

    public function currentMonth(): int
    {
        $elapsed = max(0, self::daysBetween($this->start_date, Carbon::today()));

        return min($this->durationMonths(), max(1, (int) floor($elapsed / 30.4) + 1));
    }

    public function currentWeek(): int
    {
        $elapsed = max(0, self::daysBetween($this->start_date, Carbon::today()));

        return min($this->totalWeeks(), intdiv($elapsed, 7) + 1);
    }

    /** Minggu ke- (1..totalWeeks) untuk satu tanggal. */
    public function weekOf(CarbonInterface|\DateTimeInterface $date): int
    {
        $elapsed = max(0, self::daysBetween($this->start_date, Carbon::instance($date)));

        return min($this->totalWeeks(), intdiv($elapsed, 7) + 1);
    }

    public function daysLeft(): int
    {
        return max(0, self::daysBetween(Carbon::today(), $this->end_date));
    }

    /** Pastikan nomor minggu berada di rentang 1..totalWeeks (null = minggu berjalan). */
    public function clampWeek(?int $week): int
    {
        return min($this->totalWeeks(), max(1, $week ?? $this->currentWeek()));
    }

    // ---------- Progres ----------

    public function percent(): int
    {
        $total = $this->tasks_count ?? $this->tasks()->count();
        $done = $this->completed_tasks_count ?? $this->tasks()->where('status', 'completed')->count();

        return $total > 0 ? (int) round($done / $total * 100) : 0;
    }

    // ---------- Jalur KP ----------

    public function isSeminar(): bool
    {
        return $this->program === 'seminar';
    }

    public function isMbkm(): bool
    {
        return $this->program === 'magang' && $this->program_type === 'mbkm';
    }

    /** Hanya jalur magang yang punya perusahaan dan Company Supervisor. */
    public function hasCompany(): bool
    {
        return ! $this->isSeminar() && $this->company_id !== null;
    }

    public function programLabel(): string
    {
        return self::PROGRAMS[$this->program][0] ?? ucfirst((string) $this->program);
    }

    public function programTypeLabel(): string
    {
        return self::PROGRAMS[$this->program][1][$this->program_type] ?? ucfirst((string) $this->program_type);
    }

    /** Nama tempat atau kegiatan KP untuk ditampilkan: perusahaan (magang) atau topik seminar. */
    public function placeName(): string
    {
        if ($this->isSeminar()) {
            return $this->topic ?: 'Seminar';
        }

        return $this->company?->name ?? '–';
    }

    /** Baris kedua di bawah nama tempat: divisi dan posisi (magang) atau penyelenggara (seminar). */
    public function placeDetail(): ?string
    {
        if ($this->isSeminar()) {
            return $this->organizer ? 'Penyelenggara: '.$this->organizer : $this->programTypeLabel();
        }

        $parts = array_filter([$this->division?->name ? 'Divisi '.$this->division->name : null, $this->position]);

        return $parts ? implode(' · ', $parts) : null;
    }

    /**
     * Siapa yang meninjau silabus, laporan, dan penilaian dari sisi tempat KP:
     * Company Supervisor untuk magang, dosen pembimbing untuk seminar.
     */
    public function isReviewedBy(User $user): bool
    {
        return $this->isSeminar()
            ? (int) $this->lecturer_id === (int) $user->id
            : $this->isSupervisedBy($user);
    }

    /**
     * Kunci kloter: mahasiswa di perusahaan, divisi, dan periode yang sama dianggap satu kloter.
     * Dipakai untuk membedakan dua kelompok magang dengan waktu berbeda dalam satu divisi.
     */
    public function cohortKey(): string
    {
        return implode('|', [$this->company_id, $this->division_id, $this->start_date?->toDateString(), $this->end_date?->toDateString()]);
    }

    public function cohortLabel(): string
    {
        $from = $this->start_date->locale('id');
        $to = $this->end_date->locale('id');
        $range = $from->year === $to->year
            ? $from->translatedFormat('M').'–'.$to->translatedFormat('M Y')
            : $from->translatedFormat('M Y').'–'.$to->translatedFormat('M Y');

        return 'Kloter '.$range;
    }

    public function periodLabel(): string
    {
        return $this->start_date->locale('id')->translatedFormat('j M Y').' – '.$this->end_date->locale('id')->translatedFormat('j M Y');
    }

    public function syllabusStatusLabel(): string
    {
        return self::SYLLABUS_STATUSES[$this->syllabus_status][0] ?? $this->syllabus_status;
    }

    public function syllabusStatusTone(): string
    {
        return self::SYLLABUS_STATUSES[$this->syllabus_status][1] ?? 'gray';
    }

    /**
     * Perkiraan jam di akhir KP dengan laju pencatatan sejauh ini (null jika KP belum berjalan dua minggu).
     * Dipakai untuk MBKM yang mensyaratkan logbook minimal sekian jam.
     */
    public function projectedHours(): ?float
    {
        $total = max(1, self::daysBetween($this->start_date, $this->end_date) + 1);
        $elapsed = min($total, self::daysBetween($this->start_date, Carbon::today()) + 1);
        if ($this->status !== 'berjalan' || $elapsed < 14) {
            return null;
        }

        return round($this->loggedHours() / $elapsed * $total, 1);
    }

    /** MBKM: perkiraan jam di akhir KP kurang dari syarat minimal. */
    public function isBehindMbkmHours(): bool
    {
        $projected = $this->isMbkm() ? $this->projectedHours() : null;

        return $projected !== null && $projected < (float) Campus::rule('mbkm_min_hours');
    }

    /** Total jam yang dicatat di timesheet. */
    public function loggedHours(): float
    {
        return (float) ($this->logged_hours ?? $this->timeEntries()->sum('hours'));
    }

    // ---------- Tampilan ----------

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status][0] ?? $this->status;
    }

    public function statusTone(): string
    {
        return self::STATUSES[$this->status][1] ?? 'gray';
    }
}

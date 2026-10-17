<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class Internship extends Model
{
    use SoftDeletes;

    /** Status yang membuat penempatan dianggap aktif (satu per mahasiswa). */
    public const ACTIVE_STATUSES = ['diajukan', 'berjalan'];

    /** status => [label, warna chip] */
    public const STATUSES = [
        'diajukan' => ['Diajukan', 'gray'],
        'berjalan' => ['Berjalan', 'blue'],
        'selesai' => ['Selesai', 'green'],
        'ditolak' => ['Ditolak', 'red'],
    ];

    protected $fillable = [
        'student_id',
        'company_id',
        'division_id',
        'lecturer_id',
        'academic_period_id',
        'track',
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
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Internship $internship) {
            // Menjaga aturan: satu mahasiswa hanya boleh punya satu penempatan aktif.
            $internship->active_student_id = in_array($internship->status, self::ACTIVE_STATUSES, true)
                ? $internship->student_id
                : null;

            // KP selesai: penempatan dikunci dan hanya bisa dibaca.
            if ($internship->status === 'selesai' && $internship->locked_at === null) {
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

    /** Mentor utama penempatan ini. */
    public function mentor(): HasOneThrough
    {
        return $this->hasOneThrough(User::class, InternshipStaff::class, 'internship_id', 'id', 'id', 'user_id')
            ->where('internship_staff.role', InternshipStaff::ROLE_MENTOR);
    }

    /** Ketua divisi yang mengonfirmasi penempatan ini. */
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

    /** Pasang mentor atau ketua divisi pada penempatan ini. */
    public function assignStaff(User|int $user, string $role): InternshipStaff
    {
        return $this->staff()->firstOrCreate([
            'user_id' => $user instanceof User ? $user->id : $user,
            'role' => $role,
        ]);
    }

    public function isMentoredBy(User|int $user): bool
    {
        return $this->hasStaff($user, InternshipStaff::ROLE_MENTOR);
    }

    public function isSupervisedBy(User|int $user): bool
    {
        return $this->hasStaff($user, InternshipStaff::ROLE_SUPERVISOR);
    }

    private function hasStaff(User|int $user, string $role): bool
    {
        return $this->staff()
            ->where('user_id', $user instanceof User ? $user->id : $user)
            ->where('role', $role)
            ->exists();
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(WeeklyTask::class);
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

    public function scopeMentoredBy(Builder $query, User|int $user): Builder
    {
        return $query->whereHas('staff', fn ($q) => $q
            ->where('user_id', $user instanceof User ? $user->id : $user)
            ->where('role', InternshipStaff::ROLE_MENTOR));
    }

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
            'mentor' => $query->mentoredBy($user),
            'ketua_divisi' => $query->supervisedBy($user),
            'dospem' => $query->where('lecturer_id', $user->id),
            default => $query, // kaprodi dan biro kerjasama melihat semuanya
        };
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

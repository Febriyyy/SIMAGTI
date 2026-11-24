<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Portofolio mahasiswa (satu per mahasiswa), bisa diisi sebelum mendapat penempatan:
 * judul singkat, tentang diri, minat, IPK (opsional tampil), skill (tabel student_skills),
 * proyek yang pernah dibuat, dan sertifikat pelatihan. Setelah magang, tempat magang terakhir
 * dan Company Supervisor-nya ikut tampil otomatis.
 *
 * Yang boleh melihat: pemiliknya, kaprodi/TU/admin, dosen pembimbing mahasiswa itu, Company Supervisor
 * perusahaan tempat mahasiswa mengajukan atau magang, dan siapa saja lewat tautan publik /p/{token} bila dinyalakan.
 */
class Portfolio extends Model
{
    public const MAX_SKILLS = 15;

    public const MAX_INTERESTS = 5;

    public const MAX_PROJECTS = 12;

    public const MAX_CERTIFICATES = 20;

    /** Pilihan minat bidang kerja. */
    public const INTERESTS = [
        'Backend', 'Frontend', 'Mobile', 'UI/UX', 'Data', 'AI dan Machine Learning',
        'Jaringan', 'Keamanan Siber', 'Cloud dan DevOps', 'Quality Assurance',
        'Manajemen Proyek', 'Desain Grafis', 'Digital Marketing', 'Sistem Informasi',
    ];

    /** Tingkat kemahiran skill (nilai di student_skills.level => label). */
    public const LEVELS = [
        'pemula' => 'Pemula',
        'menengah' => 'Menengah',
        'mahir' => 'Mahir',
    ];

    protected $fillable = ['user_id', 'internship_id', 'token', 'is_public', 'show_grade', 'show_gpa', 'headline', 'about', 'skills', 'interests', 'task_ids'];

    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
            'show_grade' => 'boolean',
            'show_gpa' => 'boolean',
            'skills' => 'array',
            'interests' => 'array',
            'task_ids' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Portfolio $portfolio) {
            $portfolio->token ??= Str::random(32);
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(PortfolioProject::class)->orderBy('position')->orderByDesc('year')->orderByDesc('id');
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(PortfolioCertificate::class)->orderBy('position')->orderByDesc('issued_on')->orderByDesc('id');
    }

    /** Portofolio milik mahasiswa ini; dibuat (belum publik) bila belum ada. */
    public static function forUser(User $user): self
    {
        return static::firstOrCreate(['user_id' => $user->id]);
    }

    public function publicUrl(): string
    {
        return route('portofolio.publik', $this->token);
    }

    /** KP terakhir yang sudah berjalan atau selesai (untuk bagian "Pengalaman magang"). */
    public function latestInternship(): ?Internship
    {
        return Internship::where('student_id', $this->user_id)
            ->whereIn('status', ['berjalan', 'selesai'])
            ->with(['company', 'division', 'supervisor'])
            ->orderByDesc('start_date')->orderByDesc('id')
            ->first();
    }

    public function canBeViewedBy(?User $viewer): bool
    {
        if (! $viewer) {
            return false;
        }
        if ((int) $viewer->id === (int) $this->user_id || in_array($viewer->role, ['tu', 'admin'], true)) {
            return true;
        }
        if ($viewer->role === 'kaprodi') {
            return $viewer->managesProgram(User::with('studentProfile')->find($this->user_id)?->programId());
        }
        $internships = Internship::where('student_id', $this->user_id);

        return match ($viewer->role) {
            'dospem' => (clone $internships)->where('lecturer_id', $viewer->id)->exists(),
            // Company Supervisor: mahasiswa yang dibimbingnya, atau yang mengajukan KP ke perusahaan tempat ia membimbing.
            'supervisor' => (clone $internships)->supervisedBy($viewer)->exists()
                || (clone $internships)->whereIn('company_id', Internship::supervisedBy($viewer)->pluck('company_id')->filter()->unique())->exists(),
            default => false,
        };
    }
}

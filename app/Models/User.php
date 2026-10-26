<?php

namespace App\Models;

use App\Support\Campus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    public const ROLE_LABELS = [
        'mahasiswa' => 'Mahasiswa',
        'supervisor' => 'Company Supervisor',
        'dospem' => 'Dosen Pembimbing',
        'kaprodi' => 'Kaprodi',
        'tu' => 'TU Fakultas',
        'admin' => 'Admin',
    ];

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'nim',
        'phone',
        'is_active',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function hasRole(string ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function roleLabel(): string
    {
        return self::ROLE_LABELS[$this->role] ?? ucfirst((string) $this->role);
    }

    /**
     * Label peran sesuai siapa yang melihat. Dosen pembimbing tampil sebagai "Campus Supervisor"
     * di hadapan Company Supervisor (istilah yang dipakai perusahaan), dan "Dosen Pembimbing" di tempat lain.
     */
    public function roleLabelFor(?User $viewer): string
    {
        if ($this->role === 'dospem' && $viewer?->role === 'supervisor') {
            return 'Campus Supervisor';
        }

        return $this->roleLabel();
    }

    /** Nama tanpa gelar (Dr., Ir., S.Kom., M.T., ...) untuk tabel yang sempit. Nama lengkap tetap dipakai di tempat lain. */
    public function shortName(): string
    {
        $name = trim(explode(',', $this->name)[0]);
        $words = array_filter(preg_split('/\s+/', $name), fn ($w) => ! str_ends_with($w, '.'));

        return $words ? implode(' ', $words) : $this->name;
    }

    /**
     * Nama ringkas untuk daftar dan kartu (standar UI): tanpa gelar, maksimal dua kata + inisial.
     * "Dewi Lestari Wulandari, S.Kom., M.Kom." -> "Dewi Lestari W." Nama lengkap tampil di halaman detail.
     */
    public function listName(): string
    {
        return self::shortenName($this->name);
    }

    /** Versi statis listName() untuk nama yang belum punya akun (mis. supervisor dari formulir pengajuan). */
    public static function shortenName(?string $name): string
    {
        $name = trim((string) $name);
        $words = array_values(array_filter(preg_split('/\s+/', trim(explode(',', $name)[0])), fn ($w) => $w !== '' && ! str_ends_with($w, '.')));
        if (count($words) <= 2) {
            return $words ? implode(' ', $words) : $name;
        }

        return $words[0].' '.$words[1].' '.mb_strtoupper(mb_substr(end($words), 0, 1)).'.';
    }

    /** Nama depan tanpa gelar di depan (Dr., Ir., Prof.) untuk sapaan. */
    public function firstName(): string
    {
        $words = preg_split('/\s+/', trim($this->name));

        foreach ($words as $word) {
            if (! str_ends_with($word, '.')) {
                return rtrim($word, ',');
            }
        }

        return $words[0];
    }

    /**
     * Identitas akademik mahasiswa untuk tampilan perusahaan (poin 19/10 no. 7):
     * universitas, fakultas, prodi, dan angkatan.
     *
     * @return array{university: string, faculty: ?string, program: ?string, entry_year: ?int, semester: ?int}
     */
    public function academicIdentity(): array
    {
        $profile = $this->studentProfile;
        $program = $profile?->studyProgram;

        return [
            'university' => Campus::university(),
            'faculty' => $program?->faculty ?: Campus::faculty(),
            'program' => $program?->name,
            'entry_year' => $profile?->entry_year,
            'semester' => $profile?->semester(),
        ];
    }

    // ---------- Relasi ----------

    public function studentProfile(): HasOne
    {
        return $this->hasOne(StudentProfile::class);
    }

    public function lecturerProfile(): HasOne
    {
        return $this->hasOne(LecturerProfile::class);
    }

    public function staffProfile(): HasOne
    {
        return $this->hasOne(CompanyStaffProfile::class);
    }

    /** Penempatan milik mahasiswa ini. */
    public function internships(): HasMany
    {
        return $this->hasMany(Internship::class, 'student_id');
    }

    /** Penempatan yang ditangani Company Supervisor ini. */
    public function staffInternships(): BelongsToMany
    {
        return $this->belongsToMany(Internship::class, 'internship_staff')->withPivot('role')->withTimestamps();
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'student_skills')->withPivot('level')->withTimestamps();
    }
}

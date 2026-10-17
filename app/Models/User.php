<?php

namespace App\Models;

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
        'mentor' => 'Mentor',
        'ketua_divisi' => 'Ketua Divisi',
        'dospem' => 'Dosen Pembimbing',
        'kaprodi' => 'Kaprodi',
        'biro' => 'Biro Kerjasama',
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

    /** Nama tanpa gelar (Dr., Ir., S.Kom., M.T., ...) untuk tabel yang sempit. Nama lengkap tetap dipakai di tempat lain. */
    public function shortName(): string
    {
        $name = trim(explode(',', $this->name)[0]);
        $words = array_filter(preg_split('/\s+/', $name), fn ($w) => ! str_ends_with($w, '.'));

        return $words ? implode(' ', $words) : $this->name;
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

    /** Penempatan yang ditangani mentor atau ketua divisi ini. */
    public function staffInternships(): BelongsToMany
    {
        return $this->belongsToMany(Internship::class, 'internship_staff')->withPivot('role')->withTimestamps();
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'student_skills')->withPivot('level')->withTimestamps();
    }
}

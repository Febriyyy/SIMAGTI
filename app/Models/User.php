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

    public function firstName(): string
    {
        return explode(' ', trim($this->name))[0];
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

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use SoftDeletes;

    /** status => [label, warna chip] */
    public const STATUSES = [
        'mitra' => ['Mitra', 'blue'],
        'mandiri_menunggu' => ['Menunggu', 'amber'],
        'mandiri_terverifikasi' => ['Terverifikasi', 'green'],
    ];

    protected $fillable = [
        'name',
        'industry',
        'address',
        'city',
        'email',
        'phone',
        'website',
        'logo_file_id',
        'status',
        'created_by',
        'verified_by',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'verified_at' => 'datetime',
        ];
    }

    public function divisions(): HasMany
    {
        return $this->hasMany(CompanyDivision::class);
    }

    public function staff(): HasMany
    {
        return $this->hasMany(CompanyStaffProfile::class);
    }

    public function jobPostings(): HasMany
    {
        return $this->hasMany(JobPosting::class);
    }

    public function logo(): BelongsTo
    {
        return $this->belongsTo(StoredFile::class, 'logo_file_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function internships(): HasMany
    {
        return $this->hasMany(Internship::class);
    }

    /** Penilaian akhir semua mahasiswa yang pernah magang di perusahaan ini. */
    public function assessments(): HasManyThrough
    {
        return $this->hasManyThrough(Assessment::class, Internship::class);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status][0] ?? $this->status;
    }

    public function statusTone(): string
    {
        return self::STATUSES[$this->status][1] ?? 'gray';
    }
}

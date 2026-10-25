<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Certificate extends Model
{
    public const STATUSES = [
        'draft' => ['Draft', 'gray'],
        'terbit' => ['Terbit', 'green'],
        'dicabut' => ['Dicabut', 'red'],
    ];

    protected $fillable = [
        'internship_id',
        'template_id',
        'number',
        'verification_token',
        'status',
        'file_id',
        'issued_by',
        'issued_at',
        'revoked_at',
        'revoked_reason',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function internship(): BelongsTo
    {
        return $this->belongsTo(Internship::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(CertificateTemplate::class, 'template_id');
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(StoredFile::class, 'file_id');
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    protected static function booted(): void
    {
        static::creating(function (Certificate $certificate) {
            $certificate->verification_token ??= Str::random(48);
        });
    }

    /** Nomor berurutan per tahun, contoh: KP/2026/0007. */
    public static function nextNumber(?int $year = null): string
    {
        $year ??= (int) now()->format('Y');
        $count = static::where('number', 'like', "KP/{$year}/%")->count() + 1;

        return sprintf('KP/%d/%04d', $year, $count);
    }

    public function isValid(): bool
    {
        return $this->status === 'terbit';
    }
}

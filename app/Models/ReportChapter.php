<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Satu bab laporan KP beserta status persetujuannya. */
class ReportChapter extends Model
{
    /** Bab bawaan: [judul, perlu persetujuan Company Supervisor karena memuat informasi perusahaan]. */
    public const DEFAULTS = [
        1 => ['Pendahuluan', false],
        2 => ['Gambaran Umum Perusahaan', true],
        3 => ['Pelaksanaan Kerja Praktik', true],
        4 => ['Penutup', false],
    ];

    /** status => [label, warna chip] */
    public const STATUSES = [
        'belum' => ['Belum diunggah', 'gray'],
        'menunggu_supervisor' => ['Ditinjau perusahaan', 'amber'],
        'menunggu_dosen' => ['Ditinjau dosen', 'blue'],
        'revisi' => ['Perlu revisi', 'red'],
        'disetujui' => ['Disetujui', 'green'],
    ];

    protected $fillable = [
        'internship_id', 'position', 'title', 'needs_supervisor', 'status', 'file_id', 'version', 'submitted_at',
        'supervisor_approved_by', 'supervisor_approved_at', 'approved_by', 'approved_at',
        'revision_note', 'revision_by', 'revision_at',
    ];

    protected function casts(): array
    {
        return [
            'needs_supervisor' => 'boolean',
            'submitted_at' => 'datetime',
            'supervisor_approved_at' => 'datetime',
            'approved_at' => 'datetime',
            'revision_at' => 'datetime',
        ];
    }

    public function internship(): BelongsTo
    {
        return $this->belongsTo(Internship::class);
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(StoredFile::class, 'file_id');
    }

    /** Semua versi berkas bab ini, terbaru di atas. */
    public function versions(): HasMany
    {
        return $this->hasMany(ReportChapterVersion::class)->orderByDesc('version');
    }

    public function reviser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revision_by');
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status][0] ?? $this->status;
    }

    public function statusTone(): string
    {
        return self::STATUSES[$this->status][1] ?? 'gray';
    }

    public function label(): string
    {
        return 'Bab '.$this->position.' · '.$this->title;
    }

    /**
     * Siapa yang boleh membuka berkas bab ini. Bab berisi informasi perusahaan belum boleh dibaca pihak kampus
     * sebelum disetujui Company Supervisor; supervisor hanya membuka bab yang memang perlu persetujuannya.
     */
    public function canBeReadBy(User $user): bool
    {
        $internship = $this->internship;

        return match ($user->role) {
            'mahasiswa' => (int) $internship->student_id === (int) $user->id,
            'supervisor' => $this->needs_supervisor && $internship->isSupervisedBy($user),
            'dospem' => (int) $internship->lecturer_id === (int) $user->id && $this->campusMayRead(),
            'kaprodi' => $this->campusMayRead(),
            default => false,
        };
    }

    /** Pihak kampus boleh membaca: bab tanpa data perusahaan, atau yang versi terakhirnya sudah disetujui perusahaan. */
    public function campusMayRead(): bool
    {
        return ! $this->needs_supervisor || $this->supervisor_approved_at !== null;
    }
}

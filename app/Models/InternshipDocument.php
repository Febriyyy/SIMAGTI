<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Dokumen KP: surat pengantar (diterbitkan TU Fakultas), surat balasan perusahaan dan
 * pakta integritas MBKM (diunggah mahasiswa, boleh menyusul sampai batas waktu).
 */
class InternshipDocument extends Model
{
    public const LETTER = 'surat_pengantar';

    public const REPLY = 'surat_balasan';

    public const PACT = 'pakta_integritas';

    /** jenis => [label, keterangan] */
    public const TYPES = [
        self::LETTER => ['Surat pengantar', 'Diterbitkan TU Fakultas untuk dibawa ke perusahaan'],
        self::REPLY => ['Surat balasan perusahaan', 'Bukti resmi perusahaan menerima mahasiswa'],
        self::PACT => ['Pakta integritas MBKM', 'Ditandatangani mahasiswa (MB.04)'],
    ];

    /** status => [label, warna chip] */
    public const STATUSES = [
        'diminta' => ['Diminta', 'amber'],
        'terbit' => ['Terbit', 'green'],
        'menunggu' => ['Belum diunggah', 'gray'],
        'diunggah' => ['Menunggu diperiksa', 'amber'],
        'diterima' => ['Diterima', 'green'],
        'ditolak' => ['Perlu diunggah ulang', 'red'],
    ];

    protected $fillable = [
        'internship_id',
        'type',
        'status',
        'number',
        'file_id',
        'due_on',
        'note',
        'requested_by',
        'processed_by',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'due_on' => 'date',
            'processed_at' => 'datetime',
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

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function label(): string
    {
        return self::TYPES[$this->type][0] ?? $this->type;
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status][0] ?? $this->status;
    }

    public function statusTone(): string
    {
        if ($this->isOverdue()) {
            return 'red';
        }

        return self::STATUSES[$this->status][1] ?? 'gray';
    }

    /** Unggahan yang belum masuk padahal sudah lewat batas waktu. */
    public function isOverdue(): bool
    {
        return in_array($this->status, ['menunggu', 'ditolak'], true) && $this->due_on !== null && $this->due_on->isPast();
    }

    public function isComplete(): bool
    {
        return in_array($this->status, ['terbit', 'diterima'], true);
    }
}

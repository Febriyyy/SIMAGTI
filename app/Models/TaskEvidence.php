<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Bukti pengerjaan task dari mahasiswa: link (GitHub, Drive, ...) atau berkas (foto, PDF, ZIP, ...). */
class TaskEvidence extends Model
{
    // Laravel menganggap "evidence" tidak punya bentuk jamak, jadi nama tabel ditulis eksplisit.
    protected $table = 'task_evidences';

    /**
     * Jenis berkas bukti yang diterima: ekstensi => kelompok (untuk ikon dan label).
     * Sengaja tidak menerima program (.exe, .bat), skrip, HTML, atau SVG.
     */
    public const FILE_TYPES = [
        'jpg' => 'image', 'jpeg' => 'image', 'png' => 'image', 'webp' => 'image', 'gif' => 'image',
        'pdf' => 'pdf',
        'doc' => 'doc', 'docx' => 'doc', 'txt' => 'doc', 'md' => 'doc',
        'xls' => 'sheet', 'xlsx' => 'sheet', 'csv' => 'sheet',
        'ppt' => 'slide', 'pptx' => 'slide',
        'zip' => 'archive', 'rar' => 'archive', '7z' => 'archive',
        'mp4' => 'video',
    ];

    /** Isi berkas yang diterima (dicek dari isinya, bukan dari nama). */
    public const MIME_TYPES = [
        'image/jpeg', 'image/png', 'image/webp', 'image/gif',
        'application/pdf',
        'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'application/vnd.ms-office', 'application/x-ole-storage', 'application/CDFV2', 'application/encrypted',
        'text/plain', 'text/csv', 'application/csv', 'text/markdown', 'text/x-markdown',
        'application/zip', 'application/x-zip-compressed', 'application/x-zip',
        'application/x-rar', 'application/vnd.rar', 'application/x-rar-compressed',
        'application/x-7z-compressed',
        'video/mp4',
    ];

    /** Batas ukuran per berkas (KB), berkas per kiriman, dan berkas per task. */
    public const MAX_KB = 20480;

    public const MAX_FILES = 5;

    public const MAX_FILES_PER_TASK = 15;

    protected $fillable = [
        'weekly_task_id',
        'submitted_by',
        'type',
        'title',
        'url',
        'file_id',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(WeeklyTask::class, 'weekly_task_id');
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(StoredFile::class, 'file_id');
    }

    public function isFile(): bool
    {
        return $this->type === 'file';
    }

    /** Alamat yang dibuka saat bukti diklik. */
    public function target(): ?string
    {
        if ($this->isFile()) {
            return route('tugas.bukti.show', [$this->weekly_task_id, $this->id]);
        }

        return $this->url;
    }

    /** Nama yang ditampilkan di daftar bukti. */
    public function label(): string
    {
        if ($this->isFile()) {
            return $this->file?->original_name ?? 'Berkas';
        }

        return $this->title ?: (string) preg_replace('#^https?://(www\.)?#', '', (string) $this->url);
    }

    /** Ikon untuk komponen <x-ikon>. */
    public function icon(): string
    {
        if (! $this->isFile()) {
            return str_contains((string) $this->url, 'github.com') ? 'code' : 'link';
        }

        return match (self::FILE_TYPES[$this->file?->extension()] ?? null) {
            'image' => 'image',
            'pdf' => 'pdf',
            'sheet' => 'sheet',
            'slide' => 'slide',
            'archive' => 'archive',
            'video' => 'video',
            default => 'file',
        };
    }

    /** Daftar ekstensi untuk teks bantuan dan atribut accept di form. */
    public static function acceptList(): string
    {
        return implode(',', array_map(fn ($ext) => '.'.$ext, array_keys(self::FILE_TYPES)));
    }
}

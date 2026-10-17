<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Materi dari mentor yang dilampirkan pada task (PDF, PPT, gambar). */
class TaskAttachment extends Model
{
    /** Jenis berkas yang diterima: ekstensi => label. */
    public const TYPES = [
        'pdf' => 'PDF',
        'ppt' => 'PowerPoint',
        'pptx' => 'PowerPoint',
        'jpg' => 'Gambar',
        'jpeg' => 'Gambar',
        'png' => 'Gambar',
        'webp' => 'Gambar',
    ];

    /** Batas ukuran per berkas (KB) dan jumlah berkas per task. */
    public const MAX_KB = 10240;

    public const MAX_FILES = 5;

    protected $fillable = [
        'weekly_task_id',
        'file_id',
        'uploaded_by',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(WeeklyTask::class, 'weekly_task_id');
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(StoredFile::class, 'file_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /** Aturan validasi berkas, dipakai saat membuat task maupun menambah lampiran. */
    public static function rules(): array
    {
        return [
            'lampiran' => ['nullable', 'array', 'max:'.self::MAX_FILES],
            'lampiran.*' => [
                'file',
                'max:'.self::MAX_KB,
                // nama berkas harus berakhiran salah satu jenis ini...
                'extensions:'.implode(',', array_keys(self::TYPES)),
                // ...dan isi berkasnya juga harus sesuai (bukan program yang diganti namanya)
                'mimetypes:application/pdf,application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.presentation,application/zip,application/vnd.ms-office,application/x-ole-storage,application/CDFV2,image/jpeg,image/png,image/webp',
            ],
        ];
    }

    public static function messages(): array
    {
        return [
            'lampiran.max' => 'Maksimal '.self::MAX_FILES.' berkas per task.',
            'lampiran.*.max' => 'Ukuran tiap berkas maksimal '.(self::MAX_KB / 1024).' MB.',
            'lampiran.*.extensions' => 'Berkas harus berupa PDF, PowerPoint (PPT/PPTX), atau gambar (JPG/PNG/WEBP).',
            'lampiran.*.mimetypes' => 'Isi berkas tidak sesuai jenisnya. Gunakan PDF, PowerPoint, atau gambar.',
            'lampiran.*.file' => 'Berkas gagal diunggah. Coba lagi.',
        ];
    }
}

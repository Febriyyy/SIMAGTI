<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Komentar pada satu bab laporan atau laporan akhir (report_chapter_id kosong).
 * Bisa berlampiran berkas, misalnya berkas koreksi dari dosen pembimbing.
 */
class ReportComment extends Model
{
    protected $fillable = ['internship_id', 'report_chapter_id', 'user_id', 'body', 'file_id'];

    public function internship(): BelongsTo
    {
        return $this->belongsTo(Internship::class);
    }

    public function chapter(): BelongsTo
    {
        return $this->belongsTo(ReportChapter::class, 'report_chapter_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(StoredFile::class, 'file_id');
    }
}

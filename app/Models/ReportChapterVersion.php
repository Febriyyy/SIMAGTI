<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Satu versi berkas bab laporan; versi terbaru juga tercatat di report_chapters.file_id. */
class ReportChapterVersion extends Model
{
    protected $fillable = ['report_chapter_id', 'file_id', 'version', 'submitted_at'];

    protected function casts(): array
    {
        return ['submitted_at' => 'datetime'];
    }

    public function chapter(): BelongsTo
    {
        return $this->belongsTo(ReportChapter::class, 'report_chapter_id');
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(StoredFile::class, 'file_id');
    }
}

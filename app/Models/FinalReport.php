<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Laporan akhir KP utuh (gabungan semua bab) yang diunggah mahasiswa dan dinilai dosen pembimbing. */
class FinalReport extends Model
{
    protected $fillable = ['internship_id', 'file_id', 'version', 'submitted_at'];

    protected function casts(): array
    {
        return ['submitted_at' => 'datetime'];
    }

    public function internship(): BelongsTo
    {
        return $this->belongsTo(Internship::class);
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(StoredFile::class, 'file_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Sertifikat pelatihan (bootcamp, kursus, lomba) yang diunggah mahasiswa ke portofolio. Tidak diverifikasi kampus. */
class PortfolioCertificate extends Model
{
    protected $fillable = ['portfolio_id', 'name', 'issuer', 'issued_on', 'credential_url', 'file_id', 'position'];

    protected function casts(): array
    {
        return ['issued_on' => 'date'];
    }

    public function portfolio(): BelongsTo
    {
        return $this->belongsTo(Portfolio::class);
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(StoredFile::class, 'file_id');
    }
}

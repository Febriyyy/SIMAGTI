<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CompanyDivision extends Model
{
    protected $fillable = [
        'company_id',
        'name',
        'head_name',
        'head_signature_file_id',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** Tanda tangan ketua divisi (gambar) untuk sertifikat KP. */
    public function headSignature(): BelongsTo
    {
        return $this->belongsTo(StoredFile::class, 'head_signature_file_id');
    }

    public function internships(): HasMany
    {
        return $this->hasMany(Internship::class, 'division_id');
    }
}

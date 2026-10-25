<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CertificateTemplate extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'scope',
        'company_id',
        'name',
        'logo_file_id',
        'signature_file_id',
        'stamp_file_id',
        'signer_name',
        'signer_title',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function logo(): BelongsTo
    {
        return $this->belongsTo(StoredFile::class, 'logo_file_id');
    }

    public function signature(): BelongsTo
    {
        return $this->belongsTo(StoredFile::class, 'signature_file_id');
    }

    public function stamp(): BelongsTo
    {
        return $this->belongsTo(StoredFile::class, 'stamp_file_id');
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class, 'template_id');
    }
}

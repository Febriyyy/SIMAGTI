<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LetterRequest extends Model
{
    /** status => [label, warna chip] */
    public const STATUSES = [
        'diajukan' => ['Diajukan', 'gray'],
        'diproses' => ['Diproses', 'blue'],
        'selesai' => ['Selesai', 'green'],
        'ditolak' => ['Ditolak', 'red'],
    ];

    protected $fillable = [
        'student_id',
        'company_id',
        'company_name',
        'purpose',
        'status',
        'processed_by',
        'processed_at',
        'result_file_id',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'processed_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function resultFile(): BelongsTo
    {
        return $this->belongsTo(StoredFile::class, 'result_file_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobPosting extends Model
{
    use SoftDeletes;

    /** status => [label, warna chip] */
    public const STATUSES = [
        'draft' => ['Draft', 'gray'],
        'pending' => ['Menunggu persetujuan TU', 'amber'],
        'rejected' => ['Perlu perbaikan', 'red'],
        'published' => ['Dibuka', 'green'],
        'closed' => ['Ditutup', 'gray'],
    ];

    protected $fillable = [
        'company_id',
        'posted_by',
        'title',
        'description',
        'requirements',
        'location',
        'quota',
        'deadline',
        'status',
        'published_at',
        'review_note',
        'reviewed_by',
        'reviewed_at',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'deadline' => 'date',
            'published_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'submitted_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function poster(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status][0] ?? $this->status;
    }

    public function statusTone(): string
    {
        return self::STATUSES[$this->status][1] ?? 'gray';
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'job_posting_skill');
    }
}

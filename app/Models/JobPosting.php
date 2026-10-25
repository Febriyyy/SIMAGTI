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
        'published' => ['Dibuka', 'green'],
        'closed' => ['Ditutup', 'red'],
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
    ];

    protected function casts(): array
    {
        return [
            'deadline' => 'date',
            'published_at' => 'datetime',
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

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'job_posting_skill');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyStaffProfile extends Model
{
    protected $fillable = [
        'user_id',
        'company_id',
        'division_id',
        'job_title',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(CompanyDivision::class, 'division_id');
    }
}

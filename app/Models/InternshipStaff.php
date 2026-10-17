<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InternshipStaff extends Model
{
    public const ROLE_MENTOR = 'mentor';
    public const ROLE_SUPERVISOR = 'ketua_divisi';

    protected $table = 'internship_staff';

    protected $fillable = [
        'internship_id',
        'user_id',
        'role',
    ];

    public function internship(): BelongsTo
    {
        return $this->belongsTo(Internship::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

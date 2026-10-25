<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Skill extends Model
{
    protected $fillable = [
        'name',
        'category',
    ];

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'student_skills', 'skill_id', 'user_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudyProgram extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'faculty',
    ];

    public function students(): HasMany
    {
        return $this->hasMany(StudentProfile::class);
    }

    public function lecturers(): HasMany
    {
        return $this->hasMany(LecturerProfile::class);
    }
}

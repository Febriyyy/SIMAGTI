<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeeklyMeeting extends Model
{
    protected $fillable = [
        'internship_id',
        'week_number',
        'day',
        'time',
        'url',
        'created_by',
    ];

    public function internship(): BelongsTo
    {
        return $this->belongsTo(Internship::class);
    }

    public function dayLabel(): string
    {
        return WeeklyTask::DAYS[$this->day] ?? ucfirst($this->day);
    }

    public function dayOrder(): int
    {
        return (int) array_search($this->day, array_keys(WeeklyTask::DAYS), true);
    }

    /** 10:00 -> 10.00 */
    public function timeLabel(): string
    {
        return str_replace(':', '.', $this->time);
    }
}

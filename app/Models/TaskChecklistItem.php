<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskChecklistItem extends Model
{
    protected $fillable = [
        'weekly_task_id',
        'text',
        'is_done',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'is_done' => 'boolean',
        ];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(WeeklyTask::class, 'weekly_task_id');
    }
}

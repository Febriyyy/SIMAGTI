<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskTemplateItem extends Model
{
    protected $fillable = [
        'task_template_id',
        'week_number',
        'day',
        'title',
        'description',
        'sort_order',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(TaskTemplate::class, 'task_template_id');
    }
}

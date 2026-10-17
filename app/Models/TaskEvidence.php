<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskEvidence extends Model
{
    // Laravel menganggap "evidence" tidak punya bentuk jamak, jadi nama tabel ditulis eksplisit.
    protected $table = 'task_evidences';

    protected $fillable = [
        'weekly_task_id',
        'submitted_by',
        'type',
        'title',
        'url',
        'file_id',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(WeeklyTask::class, 'weekly_task_id');
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(StoredFile::class, 'file_id');
    }

    /** Alamat yang dibuka saat bukti diklik. */
    public function target(): ?string
    {
        return $this->type === 'link' ? $this->url : null;
    }
}

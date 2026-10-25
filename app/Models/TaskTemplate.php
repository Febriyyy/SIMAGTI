<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaskTemplate extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'owner_id',
        'company_id',
        'name',
        'description',
        'is_shared',
    ];

    protected function casts(): array
    {
        return [
            'is_shared' => 'boolean',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(TaskTemplateItem::class);
    }

    /** Salin semua isi template menjadi task nyata pada satu penempatan. */
    public function applyTo(Internship $internship, int $createdBy, int $weekOffset = 0): int
    {
        $count = 0;
        $column = $internship->ensureBoard()->first();
        $days = array_keys(WeeklyTask::DAYS);

        foreach ($this->items()->orderBy('week_number')->orderBy('sort_order')->get() as $item) {
            $week = $item->week_number + $weekOffset;

            if ($week < 1 || $week > $internship->totalWeeks()) {
                continue;
            }

            // Tenggat = hari template di minggu KP tersebut (minggu ke-1 mulai di tanggal mulai KP).
            $dayIndex = max(0, (int) array_search($item->day, $days, true));
            $due = $internship->start_date->copy()->addWeeks($week - 1)->addDays($dayIndex);

            WeeklyTask::create([
                'internship_id' => $internship->id,
                'board_column_id' => $column?->id,
                'day' => $item->day,
                'title' => $item->title,
                'description' => $item->description,
                'status' => 'todo',
                'priority' => 'medium',
                'due_date' => $due->min($internship->end_date),
                'created_by' => $createdBy,
            ]);
            $count++;
        }

        return $count;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Kolom board kegiatan mahasiswa. Nama bebas; kategori dipakai sistem untuk menghitung progres. */
class BoardColumn extends Model
{
    /** kategori => [label, status task, warna chip] */
    public const CATEGORIES = [
        'todo' => ['Belum dikerjakan', 'todo', 'gray'],
        'in_progress' => ['Sedang dikerjakan', 'in_progress', 'blue'],
        'done' => ['Selesai', 'completed', 'green'],
    ];

    /** Kolom bawaan: [nama, kategori] */
    public const DEFAULTS = [
        ['To Do', 'todo'],
        ['In Progress', 'in_progress'],
        ['Review', 'in_progress'],
        ['Done', 'done'],
    ];

    protected $fillable = [
        'internship_id',
        'name',
        'category',
        'position',
    ];

    public function internship(): BelongsTo
    {
        return $this->belongsTo(Internship::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(WeeklyTask::class)->orderBy('position');
    }

    /** Status task (todo | in_progress | completed) untuk kolom ini. */
    public function taskStatus(): string
    {
        return self::CATEGORIES[$this->category][1] ?? 'todo';
    }

    public function tone(): string
    {
        return self::CATEGORIES[$this->category][2] ?? 'gray';
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Proyek milik mahasiswa; task di dalamnya diberi kode seperti Jira (PMS-12). */
class Project extends Model
{
    /** Warna proyek dari palet aplikasi. */
    public const COLORS = ['green', 'blue', 'amber', 'pink', 'purple', 'gray'];

    protected $fillable = [
        'internship_id',
        'name',
        'key',
        'description',
        'color',
        'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'archived_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Project $project) {
            $project->key = mb_strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $project->key));
        });
    }

    public function internship(): BelongsTo
    {
        return $this->belongsTo(Internship::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(WeeklyTask::class);
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    /** Usulan kode dari nama proyek: "Modul Pemesanan" → "MP", "Dashboard" → "DAS". */
    public static function suggestKey(string $name): string
    {
        $words = array_values(array_filter(preg_split('/[^A-Za-z0-9]+/', $name)));

        $key = count($words) > 1
            ? implode('', array_map(fn ($w) => mb_substr($w, 0, 1), array_slice($words, 0, 4)))
            : mb_substr($words[0] ?? 'KP', 0, 3);

        return mb_strtoupper($key ?: 'KP');
    }
}

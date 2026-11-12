<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/** Portofolio KP publik pilihan mahasiswa (satu per KP). Tautan publik: /p/{token}, aktif hanya bila is_public. */
class Portfolio extends Model
{
    public const MAX_TASKS = 6;

    public const MAX_SKILLS = 10;

    protected $fillable = ['internship_id', 'token', 'is_public', 'show_grade', 'headline', 'about', 'skills', 'task_ids'];

    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
            'show_grade' => 'boolean',
            'skills' => 'array',
            'task_ids' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Portfolio $portfolio) {
            $portfolio->token ??= Str::random(32);
        });
    }

    public function internship(): BelongsTo
    {
        return $this->belongsTo(Internship::class);
    }

    /** Portofolio milik KP ini; dibuat (belum publik) bila belum ada. */
    public static function for(Internship $internship): self
    {
        return static::firstOrCreate(['internship_id' => $internship->id]);
    }

    public function publicUrl(): string
    {
        return route('portofolio.publik', $this->token);
    }
}

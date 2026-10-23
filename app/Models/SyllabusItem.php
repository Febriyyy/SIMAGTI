<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Satu tujuan di silabus mahasiswa. Dasar penilaian perusahaan nanti. */
class SyllabusItem extends Model
{
    /** kategori => [label, keterangan, ikon, warna] */
    public const CATEGORIES = [
        'hard' => ['Hard skill', 'Kemampuan teknis yang ingin dikuasai', 'code', 'blue'],
        'soft' => ['Soft skill', 'Komunikasi, kerja sama, dan cara bekerja', 'users', 'purple'],
        'attitude' => ['Attitude', 'Sikap kerja: disiplin, tanggung jawab, etika', 'verify', 'green'],
    ];

    protected $fillable = [
        'internship_id',
        'category',
        'title',
        'description',
        'position',
    ];

    public function internship(): BelongsTo
    {
        return $this->belongsTo(Internship::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(WeeklyTask::class);
    }

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category][0] ?? $this->category;
    }

    public function tone(): string
    {
        return self::CATEGORIES[$this->category][3] ?? 'gray';
    }
}

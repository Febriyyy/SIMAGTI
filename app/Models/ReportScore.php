<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Nilai laporan akhir dari dosen pembimbing: empat aspek dengan poin maksimal sesuai bobot, total 0–100. */
class ReportScore extends Model
{
    /** kolom => [label, poin maksimal] */
    public const ASPECTS = [
        'writing' => ['Tata penulisan laporan', 35],
        'analysis' => ['Kemampuan pengamatan dan pembahasan', 25],
        'explaining' => ['Kemampuan menjelaskan laporan', 25],
        'conduct' => ['Penampilan dan sikap', 15],
    ];

    protected $fillable = ['internship_id', 'writing', 'analysis', 'explaining', 'conduct', 'total', 'note', 'graded_by', 'graded_at'];

    protected function casts(): array
    {
        return ['graded_at' => 'datetime'];
    }

    public function internship(): BelongsTo
    {
        return $this->belongsTo(Internship::class);
    }

    public function grader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'graded_by');
    }
}

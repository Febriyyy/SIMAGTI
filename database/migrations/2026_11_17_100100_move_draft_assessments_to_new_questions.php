<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Draf penilaian (belum dikirim) yang masih memakai rubrik pertanyaan bawaan lama dipindah ke rubrik aktif
 * yang berisi rumusan pertanyaan baru. Jawaban ikut dipindah per urutan, karena jumlah, urutan, dan kategorinya sama.
 * Penilaian yang sudah dikirim atau divalidasi tidak disentuh.
 */
return new class extends Migration
{
    public function up(): void
    {
        $active = DB::table('assessment_rubrics')->where('is_active', true)->orderByDesc('id')->first();
        if (! $active) {
            return;
        }
        $newAspects = DB::table('rubric_aspects')->where('rubric_id', $active->id)->orderBy('sort_order')->orderBy('id')->get()->values();

        $drafts = DB::table('assessments')->where('status', 'draft')->whereNull('submitted_at')->where('rubric_id', '!=', $active->id)->get();
        foreach ($drafts as $draft) {
            $oldAspects = DB::table('rubric_aspects')->where('rubric_id', $draft->rubric_id)->orderBy('sort_order')->orderBy('id')->get()->values();

            // Hanya bila strukturnya sama persis (jumlah dan kategori per urutan)
            if ($oldAspects->count() !== $newAspects->count()
                || $oldAspects->pluck('category')->all() !== $newAspects->pluck('category')->all()) {
                continue;
            }

            DB::transaction(function () use ($draft, $oldAspects, $newAspects, $active) {
                foreach ($oldAspects as $k => $old) {
                    DB::table('assessment_scores')->where('assessment_id', $draft->id)->where('rubric_aspect_id', $old->id)
                        ->update(['rubric_aspect_id' => $newAspects[$k]->id]);
                }
                DB::table('assessments')->where('id', $draft->id)->update(['rubric_id' => $active->id]);
            });
        }
    }

    public function down(): void
    {
        // Tidak dikembalikan.
    }
};

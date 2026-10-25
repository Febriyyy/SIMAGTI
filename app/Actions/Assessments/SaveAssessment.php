<?php

namespace App\Actions\Assessments;

use App\Models\ActivityLog;
use App\Models\Assessment;
use App\Models\Internship;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Company Supervisor (seminar: dosen pembimbing) menyimpan penilaian akhir (draft) atau mengirimnya untuk divalidasi dosen pembimbing.
 * Nilai akhir dihitung ulang dari bobot rubrik setiap kali disimpan.
 */
class SaveAssessment
{
    /**
     * @param  array<int|string, array{score: int|float|string, note?: string|null}>  $scores  rubric_aspect_id => isian
     */
    public function handle(User $actor, Internship $internship, array $scores, bool $submit): Assessment
    {
        return DB::transaction(function () use ($actor, $internship, $scores, $submit) {
            $assessment = $internship->assessment;
            $rubric = Assessment::rubricFor($internship);

            if (! $rubric) {
                throw ValidationException::withMessages(['scores' => 'Belum ada rubrik penilaian yang aktif.']);
            }

            if (abs($rubric->totalWeight() - 100) > 0.001) {
                throw ValidationException::withMessages(['scores' => 'Total bobot kuesioner harus 100%. Hubungi dosen pembimbing.']);
            }

            $assessment ??= Assessment::create([
                'internship_id' => $internship->id,
                'rubric_id' => $rubric->id,
                'assessor_id' => $actor->id,
                'status' => 'draft',
            ]);

            foreach ($rubric->aspects as $aspect) {
                if (! array_key_exists($aspect->id, $scores)) {
                    continue;
                }

                $assessment->scores()->updateOrCreate(
                    ['rubric_aspect_id' => $aspect->id],
                    [
                        // Pertanyaan isian: jawaban disimpan di kolom catatan, tanpa nilai
                        'score' => $aspect->isText() ? null : $scores[$aspect->id]['score'],
                        'note' => filled($scores[$aspect->id]['note'] ?? null) ? trim($scores[$aspect->id]['note']) : null,
                    ]
                );
            }

            $assessment->fill([
                'assessor_id' => $actor->id,
                'final_score' => $assessment->computeFinalScore(),
            ]);

            if ($submit) {
                $assessment->fill(['status' => 'submitted', 'submitted_at' => now()]);
            }

            $assessment->save();

            $student = $internship->student->name;
            ActivityLog::record(
                $submit ? 'penilaian.kirim' : 'penilaian.simpan',
                $submit
                    ? "Mengirim penilaian akhir {$student} untuk divalidasi"
                    : "Menyimpan draft penilaian akhir {$student}",
                $assessment
            );

            return $assessment;
        });
    }
}

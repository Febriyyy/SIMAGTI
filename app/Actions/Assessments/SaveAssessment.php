<?php

namespace App\Actions\Assessments;

use App\Models\ActivityLog;
use App\Models\Assessment;
use App\Models\Internship;
use App\Models\User;
use App\Support\AssessmentStandard;
use Illuminate\Support\Facades\DB;

/**
 * Company Supervisor menyimpan penilaian akhir (draft) atau mengirimnya untuk divalidasi dosen pembimbing.
 * Nilai akhir dihitung ulang dari standar kampus setiap kali disimpan; saat dikirim, bobot kategori ikut disimpan.
 */
class SaveAssessment
{
    /**
     * @param  array<int, float>  $scores  rubric_aspect_id => nilai skala yang dipilih
     */
    public function handle(User $actor, Internship $internship, array $scores, bool $submit, ?string $comment = null): Assessment
    {
        return DB::transaction(function () use ($actor, $internship, $scores, $submit, $comment) {
            $rubric = Assessment::rubricFor($internship);

            $assessment = $internship->assessment ?? Assessment::create([
                'internship_id' => $internship->id,
                'rubric_id' => $rubric->id,
                'assessor_id' => $actor->id,
                'status' => 'draft',
            ]);

            foreach ($rubric->aspects as $aspect) {
                if (array_key_exists($aspect->id, $scores)) {
                    $assessment->scores()->updateOrCreate(['rubric_aspect_id' => $aspect->id], ['score' => $scores[$aspect->id]]);
                }
            }

            $assessment->fill([
                'assessor_id' => $actor->id,
                'comment' => $comment,
                'category_weights' => $submit ? AssessmentStandard::weights() : null,
            ]);
            $assessment->final_score = $assessment->computeFinalScore();

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

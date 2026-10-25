<?php

namespace App\Actions\Assessments;

use App\Models\ActivityLog;
use App\Models\Assessment;
use App\Models\Internship;
use App\Models\User;
use App\Support\Num;
use Illuminate\Support\Facades\DB;

/**
 * Dosen pembimbing memvalidasi penilaian (nilai dan huruf mutu dikunci permanen)
 * atau mengembalikannya ke penilai (Company Supervisor) dengan catatan.
 */
class ReviewAssessment
{
    public function handle(User $actor, Internship $internship, string $decision, ?string $note = null): Assessment
    {
        return DB::transaction(function () use ($actor, $internship, $decision, $note) {
            // Kunci baris supaya dua klik bersamaan tidak memproses dua kali.
            $assessment = Assessment::whereKey($internship->assessment->id)->lockForUpdate()->firstOrFail();
            $note = filled($note) ? trim($note) : null;
            $student = $internship->student->name;

            if ($decision === 'validasi') {
                $assessment->validateBy($actor, $note);

                ActivityLog::record(
                    'penilaian.validasi',
                    "Memvalidasi penilaian akhir {$student}: {$assessment->letter_grade} (".Num::short($assessment->final_score).")",
                    $assessment
                );
            } else {
                $assessment->update([
                    'status' => 'draft',
                    'submitted_at' => null,
                    'supervisor_note' => $note,
                ]);

                ActivityLog::record('penilaian.kembalikan', "Mengembalikan penilaian akhir {$student} ke penilai", $assessment);
            }

            return $assessment;
        });
    }
}

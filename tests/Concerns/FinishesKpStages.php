<?php

namespace Tests\Concerns;

use App\Actions\Results\RecordDefense;
use App\Models\FinalReport;
use App\Models\Internship;
use App\Models\ReportScore;
use App\Models\User;

/**
 * Menuntaskan tahap akhir KP untuk pengujian: masa magang berakhir, laporan akhir diunggah dan dinilai dosen,
 * lalu nilai sidang disahkan kaprodi. Panggil setelah penilaian Company Supervisor divalidasi.
 */
trait FinishesKpStages
{
    protected function finishReportAndDefense(Internship $internship): Internship
    {
        $internship->forceFill(['end_date' => today()->subDay()])->saveQuietly();

        FinalReport::updateOrCreate(['internship_id' => $internship->id], ['version' => 1, 'submitted_at' => now()]);
        ReportScore::updateOrCreate(['internship_id' => $internship->id], [
            'writing' => 30, 'analysis' => 22, 'explaining' => 22, 'conduct' => 13, 'total' => 87,
            'graded_by' => $internship->lecturer_id, 'graded_at' => now(),
        ]);

        $kaprodi = User::where('role', 'kaprodi')->firstOrFail();
        app(RecordDefense::class)->handle($kaprodi, $internship->fresh(), ['defended_at' => today()->toDateString()]);

        return $internship->fresh();
    }
}

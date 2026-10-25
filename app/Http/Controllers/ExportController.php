<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Internship;
use App\Support\ActiveInternship;
use App\Support\ActivityReport;
use App\Support\TaskFilter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/** Unduh laporan kegiatan (timesheet + task per proyek): Excel untuk diolah, PDF (halaman cetak) untuk arsip. */
class ExportController extends Controller
{
    public function mine(Request $request): Response
    {
        $internship = ActiveInternship::for($request->user());
        abort_unless($internship, 404);

        return $this->export($request, $internship);
    }

    public function internship(Request $request, Internship $internship): Response
    {
        Gate::authorize('view', $internship);

        return $this->export($request, $internship);
    }

    private function export(Request $request, Internship $internship): Response
    {
        $internship->load(['student.studentProfile.studyProgram', 'company', 'division', 'supervisor', 'lecturer']);
        $filter = TaskFilter::fromRequest($internship, $request);
        $report = ActivityReport::build($internship, $filter);
        $name = 'kegiatan-kp-'.Str::slug($internship->student->name).'-'.now()->format('Ymd');

        ActivityLog::record('kegiatan.unduh', 'Mengunduh laporan kegiatan '.$internship->student->name, $internship);

        if ($request->query('format') === 'xlsx') {
            return response()->download(ActivityReport::xlsx($internship, $report), $name.'.xlsx', [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend();
        }

        return response()->view('export.kegiatan', compact('internship', 'report', 'filter'))
            ->header('Cache-Control', 'private, no-store');
    }
}

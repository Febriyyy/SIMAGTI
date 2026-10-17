<?php

namespace App\Http\Controllers;

use App\Actions\Assessments\SaveAssessment;
use App\Http\Requests\Assessments\SaveAssessmentRequest;
use App\Models\Assessment;
use App\Models\Internship;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/** Mentor: penilaian akhir mahasiswa bimbingan. */
class AssessmentController extends Controller
{
    public function index(Request $request): View
    {
        $internships = Internship::with(['student', 'company', 'assessment'])
            ->withProgress()
            ->mentoredBy($request->user())
            ->whereIn('status', ['berjalan', 'selesai'])
            ->orderBy('end_date')
            ->get();

        return view('penilaian.mentor-index', compact('internships'));
    }

    public function edit(Request $request, Internship $internship): View
    {
        Gate::authorize('view', $internship);

        $internship->load(['student', 'company', 'supervisor', 'assessment.scores', 'assessment.validator']);
        $assessment = $internship->assessment;
        $rubric = Assessment::rubricFor($internship);
        $canEdit = $request->user()->can('assess', $internship);

        return view('penilaian.form', compact('internship', 'assessment', 'rubric', 'canEdit'));
    }

    public function update(SaveAssessmentRequest $request, Internship $internship, SaveAssessment $save): RedirectResponse
    {
        $save->handle($request->user(), $internship, $request->scores(), $request->submitting());

        return $request->submitting()
            ? redirect()->route('mentor.penilaian')->with('status', 'Penilaian dikirim ke ketua divisi untuk divalidasi.')
            : redirect()->route('mentor.penilaian.edit', $internship)->with('status', 'Draft penilaian tersimpan.');
    }
}

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

/** Penilaian akhir oleh Company Supervisor di akhir periode KP, memakai standar penilaian kampus. */
class AssessmentController extends Controller
{
    public function index(Request $request): View
    {
        $internships = Internship::with(['student', 'company', 'assessment'])
            ->withProgress()
            ->supervisedBy($request->user())
            ->whereIn('status', ['berjalan', 'selesai'])
            ->orderBy('end_date')
            ->get();

        return view('penilaian.index', compact('internships'));
    }

    public function edit(Request $request, Internship $internship): View
    {
        Gate::authorize('view', $internship);

        $internship->load(['student', 'company', 'supervisor', 'lecturer', 'assessment.scores', 'assessment.validator']);
        $assessment = $internship->assessment;
        $rubric = Assessment::rubricFor($internship);
        $canEdit = $request->user()->can('assess', $internship);

        return view('penilaian.form', compact('internship', 'assessment', 'rubric', 'canEdit'));
    }

    public function update(SaveAssessmentRequest $request, Internship $internship, SaveAssessment $save): RedirectResponse
    {
        $save->handle($request->user(), $internship, $request->scores(), $request->submitting(), $request->comment());

        return $request->submitting()
            ? redirect()->route('penilaian.index')->with('status', 'Penilaian dikirim ke dosen pembimbing untuk divalidasi.')
            : redirect()->route('penilaian.edit', $internship)->with('status', 'Draft penilaian tersimpan.');
    }
}

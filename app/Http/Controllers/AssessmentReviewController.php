<?php

namespace App\Http\Controllers;

use App\Actions\Assessments\ReviewAssessment;
use App\Http\Requests\Assessments\ReviewAssessmentRequest;
use App\Models\Assessment;
use App\Models\Internship;
use App\Support\Num;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/** Dosen pembimbing: validasi penilaian akhir yang dikirim Company Supervisor. */
class AssessmentReviewController extends Controller
{
    public function index(Request $request): View
    {
        $internships = Internship::with(['student', 'company', 'supervisor', 'assessment'])
            ->where('lecturer_id', $request->user()->id)
            ->whereIn('status', ['berjalan', 'selesai'])
            ->get()
            // Yang menunggu validasi di atas, lalu draft, lalu yang sudah tervalidasi.
            ->sortBy(fn (Internship $i) => match ($i->assessment?->status) {
                'submitted' => 0,
                'draft' => 1,
                null => 2,
                default => 3,
            })
            ->values();

        return view('penilaian.validasi-index', compact('internships'));
    }

    public function show(Request $request, Internship $internship): View
    {
        Gate::authorize('view', $internship);

        $internship->load(['student', 'company', 'supervisor', 'assessment.scores', 'assessment.assessor', 'assessment.validator']);
        $assessment = $internship->assessment;
        $rubric = Assessment::rubricFor($internship);
        $canReview = $request->user()->can('validateAssessment', $internship);

        return view('penilaian.review', compact('internship', 'assessment', 'rubric', 'canReview'));
    }

    public function update(ReviewAssessmentRequest $request, Internship $internship, ReviewAssessment $review): RedirectResponse
    {
        $data = $request->validated();
        $assessment = $review->handle($request->user(), $internship, $data['keputusan'], $data['catatan'] ?? null);

        $message = $data['keputusan'] === 'validasi'
            ? "Penilaian {$internship->student->name} tervalidasi: {$assessment->letter_grade} (".Num::short($assessment->final_score).")."
            : "Penilaian {$internship->student->name} dikembalikan ke penilai.";

        return redirect()->route('validasi.index')->with('status', $message);
    }
}

<?php

namespace App\Http\Controllers;

use App\Actions\Results\FinalizeResult;
use App\Models\Internship;
use App\Support\Campus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Hasil akhir KP oleh dosen pembimbing. */
class ResultController extends Controller
{
    public function index(Request $request): View
    {
        $internships = Internship::with(['student', 'assessment', 'company'])
            ->where('lecturer_id', $request->user()->id)
            ->whereIn('status', ['berjalan', 'selesai'])
            ->withCount(['guidanceSessions as confirmed_count' => fn ($q) => $q->where('status', 'confirmed')])
            ->orderByRaw("CASE status WHEN 'berjalan' THEN 0 ELSE 1 END")->orderBy('end_date')
            ->get();

        return view('hasil.index', ['internships' => $internships, 'min' => (int) Campus::rule('min_guidance')]);
    }

    public function edit(Request $request, Internship $internship): View
    {
        $this->authorizeLecturer($request, $internship);
        $internship->load(['student.studentProfile.studyProgram', 'company', 'division', 'supervisor', 'assessment', 'monthlyReviews', 'creditRecognition']);

        return view('hasil.edit', [
            'internship' => $internship,
            'blockers' => FinalizeResult::blockers($internship),
            'guidance' => $internship->confirmedGuidanceCount(),
            'min' => (int) Campus::rule('min_guidance'),
            'weights' => [(int) Campus::rule('mbkm_partner_weight'), (int) Campus::rule('mbkm_lecturer_weight')],
        ]);
    }

    public function update(Request $request, Internship $internship, FinalizeResult $finalize): RedirectResponse
    {
        $this->authorizeLecturer($request, $internship);

        $data = $request->validate($internship->isMbkm()
            ? ['lecturer_score' => ['required', 'numeric', 'between:0,100'], 'result_note' => ['nullable', 'string', 'max:1000']]
            : ['result' => ['required', Rule::in(array_keys(Internship::RESULTS))], 'result_note' => ['nullable', 'string', 'max:1000']],
            [], ['lecturer_score' => 'nilai dosen', 'result' => 'hasil', 'result_note' => 'catatan']);

        $finalize->handle($request->user(), $internship, $data);

        return redirect()->route('hasil.index')->with('status', "Hasil KP {$internship->student->name} tersimpan. KP ditutup.");
    }

    private function authorizeLecturer(Request $request, Internship $internship): void
    {
        abort_unless((int) $internship->lecturer_id === (int) $request->user()->id, 403);
    }
}

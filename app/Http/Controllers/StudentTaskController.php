<?php

namespace App\Http\Controllers;

use App\Actions\Tasks\SubmitTaskEvidence;
use App\Http\Requests\Tasks\StoreEvidenceRequest;
use App\Models\ActivityLog;
use App\Models\Internship;
use App\Models\WeeklyTask;
use App\Support\ActiveInternship;
use App\Support\BoardData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Halaman Kegiatan mahasiswa: board ala Jira/ClickUp atau list dengan filter. */
class StudentTaskController extends Controller
{
    public function index(Request $request): View
    {
        $internship = ActiveInternship::for($request->user(), ['company', 'division']);

        if (! $internship) {
            return view('kegiatan.index', ['internship' => null]);
        }

        $data = BoardData::build($internship, $request);
        $data['canEdit'] = $request->user()->can('manageActivities', $internship);

        return view('kegiatan.index', $data);
    }

    /** Ritme kerja (harian / mingguan / bulanan) diatur mahasiswa sendiri (poin 13). */
    public function settings(Request $request): RedirectResponse
    {
        $internship = ActiveInternship::for($request->user());
        abort_unless($internship, 404);
        Gate::authorize('manageActivities', $internship);

        $data = $request->validate(['cadence' => ['required', Rule::in(array_keys(Internship::CADENCES))]]);
        $internship->update($data);

        ActivityLog::record('kegiatan.ritme', 'Mengubah ritme kerja menjadi '.Internship::CADENCES[$data['cadence']], $internship);

        return back()->with('status', 'Ritme kerja diubah menjadi '.mb_strtolower(Internship::CADENCES[$data['cadence']]).'.');
    }

    /** Mahasiswa mengirim bukti pengerjaan: link dan/atau berkas (foto, PDF, ZIP, ...). */
    public function evidence(StoreEvidenceRequest $request, WeeklyTask $task, SubmitTaskEvidence $submit): RedirectResponse
    {
        $data = $request->validated();

        $submit->handle($request->user(), $task, $data['evidence_url'] ?? null, $data['title'] ?? null, $request->evidenceFiles());

        return back()->with('status', 'Bukti pengerjaan tersimpan.');
    }
}

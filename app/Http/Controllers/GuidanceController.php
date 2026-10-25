<?php

namespace App\Http\Controllers;

use App\Actions\Guidance\LogGuidance;
use App\Models\GuidanceSession;
use App\Models\Internship;
use App\Support\ActiveInternship;
use App\Support\Campus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Log bimbingan dengan dosen pembimbing (minimal sesuai aturan kampus sebelum hasil KP). */
class GuidanceController extends Controller
{
    public function index(Request $request): View
    {
        $internship = ActiveInternship::for($request->user(), ['lecturer']);

        return view('bimbingan.index', [
            'internship' => $internship,
            'sessions' => $internship ? $internship->guidanceSessions()->get() : collect(),
            'min' => (int) Campus::rule('min_guidance'),
            'canEdit' => $internship && $request->user()->can('manageActivities', $internship),
        ]);
    }

    public function store(Request $request, LogGuidance $log): RedirectResponse
    {
        $internship = ActiveInternship::for($request->user());
        abort_unless($internship, 404);
        Gate::authorize('manageActivities', $internship);

        $data = $request->validate([
            'held_on' => ['required', 'date', 'after_or_equal:'.$internship->start_date->copy()->subDays(30)->toDateString(), 'before_or_equal:today'],
            'mode' => ['required', Rule::in(array_keys(GuidanceSession::MODES))],
            'topic' => ['required', 'string', 'max:200'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], ['held_on.before_or_equal' => 'Tanggal bimbingan tidak boleh di masa depan.'], ['held_on' => 'tanggal', 'topic' => 'topik', 'notes' => 'catatan']);

        $log->create($request->user(), $internship, $data);

        return back()->with('status', 'Bimbingan dicatat dan menunggu konfirmasi dosen pembimbing.');
    }

    public function destroy(Request $request, GuidanceSession $session): RedirectResponse
    {
        $internship = $session->internship;
        abort_unless((int) $internship->student_id === (int) $request->user()->id && $session->status !== 'confirmed', 403);
        Gate::authorize('manageActivities', $internship);
        $session->delete();

        return back()->with('status', 'Catatan bimbingan dihapus.');
    }

    /** Dosen pembimbing: semua bimbingan yang menunggu konfirmasi + rekap per mahasiswa. */
    public function queue(Request $request): View
    {
        $internships = Internship::with('student')->where('lecturer_id', $request->user()->id)
            ->whereIn('status', ['berjalan', 'selesai'])
            ->withCount(['guidanceSessions as confirmed_count' => fn ($q) => $q->where('status', 'confirmed')])
            ->orderBy('end_date')->get();

        $pending = GuidanceSession::with('internship.student')->whereIn('internship_id', $internships->pluck('id'))
            ->where('status', 'pending')->orderBy('held_on')->get();

        return view('bimbingan.antrean', ['internships' => $internships, 'pending' => $pending, 'min' => (int) Campus::rule('min_guidance')]);
    }

    public function decide(Request $request, GuidanceSession $session, LogGuidance $log): RedirectResponse
    {
        abort_unless((int) $session->internship->lecturer_id === (int) $request->user()->id, 403);
        $data = $request->validate([
            'keputusan' => ['required', Rule::in(['konfirmasi', 'tolak'])],
            'catatan' => ['nullable', 'string', 'max:500'],
        ]);

        $log->decide($request->user(), $session, $data['keputusan'] === 'konfirmasi', $data['catatan'] ?? null);

        return back()->with('status', $data['keputusan'] === 'konfirmasi' ? 'Bimbingan dikonfirmasi.' : 'Bimbingan ditolak.');
    }
}

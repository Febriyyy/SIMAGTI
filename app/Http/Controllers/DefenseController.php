<?php

namespace App\Http\Controllers;

use App\Actions\Results\RecordDefense;
use App\Models\Internship;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Pengesahan Nilai KP oleh kaprodi: melihat jadwal sidang (diatur dosen pembimbing, lihat DefenseScheduleController)
 * lalu mengesahkan nilainya (dosen pembimbing 50% + industri 50%).
 * Nilai sidang dicatat terpisah dari nilai akhir penilaian Company Supervisor.
 */
class DefenseController extends Controller
{
    public function index(Request $request): View
    {
        $internships = Internship::with(['student', 'lecturer', 'company', 'assessment', 'reportScore', 'finalReport', 'defenseScore', 'defenseSchedule.examiner'])
            ->visibleTo($request->user())
            ->where(fn ($q) => $q->where('status', 'selesai')
                ->orWhere(fn ($q) => $q->where('status', 'berjalan')->whereDate('end_date', '<=', today()->addDays(30))))
            ->orderBy('end_date')
            ->get();

        $done = $internships->filter(fn (Internship $i) => $i->defenseScore !== null);
        $scheduled = $internships->filter(fn (Internship $i) => $i->defenseScore === null && $i->defenseSchedule !== null)
            ->sortBy(fn (Internship $i) => $i->defenseSchedule->scheduled_at)->values();
        $unscheduled = $internships->filter(fn (Internship $i) => $i->defenseScore === null && $i->defenseSchedule === null)->values();

        return view('kaprodi.sidang', [
            'unscheduled' => $unscheduled,
            'scheduled' => $scheduled,
            'done' => $done->sortByDesc(fn ($i) => $i->defenseScore->defended_at)->values(),
        ]);
    }

    /** Kaprodi mengesahkan nilai sidang: dihitung dari nilai dosen pembimbing dan nilai industri. */
    public function update(Request $request, Internship $internship, RecordDefense $record): RedirectResponse
    {
        abort_unless($request->user()->managesProgram($internship->programId()), 403, 'KP ini milik program studi lain.');
        $data = $request->validate([
            'defended_at' => ['required', 'date', 'before_or_equal:today'],
            'note' => ['nullable', 'string', 'max:500'],
        ], [
            'defended_at.required' => 'Isi tanggal sidang.',
            'defended_at.before_or_equal' => 'Tanggal sidang tidak boleh di masa depan.',
        ]);

        $record->handle($request->user(), $internship->loadMissing(['student', 'lecturer', 'reportScore', 'assessment']), $data);

        return redirect()->route('kaprodi.sidang')->with('status', 'Nilai sidang '.rtrim($internship->student->listName(), '.').' disahkan.');
    }
}

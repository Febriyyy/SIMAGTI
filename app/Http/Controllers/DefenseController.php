<?php

namespace App\Http\Controllers;

use App\Actions\Results\RecordDefense;
use App\Actions\Results\ScheduleDefense;
use App\Models\DefenseSchedule;
use App\Models\Internship;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Sidang KP oleh kaprodi: jadwalkan sidang (waktu, tempat, penguji), lalu isi nilainya.
 * Nilai sidang dicatat terpisah dari nilai akhir penilaian Company Supervisor.
 */
class DefenseController extends Controller
{
    public function index(Request $request): View
    {
        $internships = Internship::with(['student', 'lecturer', 'company', 'assessment', 'defenseScore', 'defenseSchedule.examiner'])
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
            'examiners' => User::where('role', 'dospem')->where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function schedule(Request $request, Internship $internship, ScheduleDefense $schedule): RedirectResponse
    {
        $data = $request->validateWithBag('jadwal'.$internship->id, [
            'tanggal' => ['required', 'date', 'after_or_equal:today'],
            'jam' => ['required', 'date_format:H:i'],
            'cara' => ['required', Rule::in(array_keys(DefenseSchedule::MODES))],
            'tempat' => ['required', 'string', 'max:200'],
            'penguji' => ['nullable', Rule::exists('users', 'id')->where('role', 'dospem')],
            'catatan' => ['nullable', 'string', 'max:500'],
        ], [
            'tanggal.required' => 'Isi tanggal sidang.',
            'tanggal.after_or_equal' => 'Tanggal sidang tidak boleh di masa lalu.',
            'jam.required' => 'Isi jam sidang.',
            'tempat.required' => 'Isi ruang atau tautan rapat daring.',
        ]);

        $schedule->handle($request->user(), $internship->loadMissing(['student', 'lecturer', 'defenseScore', 'defenseSchedule']), $data);

        return redirect()->route('kaprodi.sidang')->with('status', 'Jadwal sidang '.rtrim($internship->student->listName(), '.').' tersimpan dan sudah diberitahukan.');
    }

    public function update(Request $request, Internship $internship, RecordDefense $record): RedirectResponse
    {
        $data = $request->validate([
            'score' => ['required', 'numeric', 'between:0,100'],
            'defended_at' => ['required', 'date', 'before_or_equal:today'],
            'note' => ['nullable', 'string', 'max:500'],
        ], [
            'score.required' => 'Isi nilai sidang.',
            'score.between' => 'Nilai sidang harus di antara 0 dan 100.',
            'defended_at.required' => 'Isi tanggal sidang.',
            'defended_at.before_or_equal' => 'Tanggal sidang tidak boleh di masa depan.',
        ]);

        $record->handle($request->user(), $internship->loadMissing('student'), $data);

        return redirect()->route('kaprodi.sidang')->with('status', 'Nilai sidang '.rtrim($internship->student->listName(), '.').' tersimpan.');
    }
}

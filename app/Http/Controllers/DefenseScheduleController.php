<?php

namespace App\Http\Controllers;

use App\Actions\Results\ScheduleDefense;
use App\Models\DefenseSchedule;
use App\Models\Internship;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Jadwal sidang KP oleh dosen pembimbing: hanya untuk mahasiswa bimbingannya sendiri.
 * Setelah sidang berlangsung, kaprodi mengesahkan nilainya di Pengesahan Nilai KP (DefenseController).
 */
class DefenseScheduleController extends Controller
{
    public function index(Request $request): View
    {
        $internships = Internship::with(['student', 'company', 'defenseScore', 'defenseSchedule.examiner'])
            ->where('lecturer_id', $request->user()->id)
            ->where(fn ($q) => $q->where('status', 'selesai')
                ->orWhere(fn ($q) => $q->where('status', 'berjalan')->whereDate('end_date', '<=', today()->addDays(30))))
            ->orderBy('end_date')
            ->get();

        return view('dospem.sidang', [
            'unscheduled' => $internships->filter(fn (Internship $i) => $i->defenseScore === null && $i->defenseSchedule === null)->values(),
            'scheduled' => $internships->filter(fn (Internship $i) => $i->defenseScore === null && $i->defenseSchedule !== null)
                ->sortBy(fn (Internship $i) => $i->defenseSchedule->scheduled_at)->values(),
            'done' => $internships->filter(fn (Internship $i) => $i->defenseScore !== null)
                ->sortByDesc(fn (Internship $i) => $i->defenseScore->defended_at)->values(),
            // Penguji: dosen lain (bukan pembimbingnya sendiri)
            'examiners' => User::where('role', 'dospem')->where('is_active', true)->whereKeyNot($request->user()->id)
                ->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function schedule(Request $request, Internship $internship, ScheduleDefense $schedule): RedirectResponse
    {
        abort_unless($internship->lecturer_id === $request->user()->id, 403, 'Hanya dosen pembimbing mahasiswa ini yang bisa menjadwalkan sidangnya.');

        $data = $request->validateWithBag('jadwal'.$internship->id, [
            'tanggal' => ['required', 'date', 'after_or_equal:today'],
            'jam' => ['required', 'date_format:H:i'],
            'cara' => ['required', Rule::in(array_keys(DefenseSchedule::MODES))],
            'tempat' => ['required', 'string', 'max:200'],
            'penguji' => ['nullable', Rule::exists('users', 'id')->where('role', 'dospem'), Rule::notIn([$request->user()->id])],
            'catatan' => ['nullable', 'string', 'max:500'],
        ], [
            'tanggal.required' => 'Isi tanggal sidang.',
            'tanggal.after_or_equal' => 'Tanggal sidang tidak boleh di masa lalu.',
            'jam.required' => 'Isi jam sidang.',
            'tempat.required' => 'Isi ruang atau tautan rapat daring.',
            'penguji.not_in' => 'Dosen penguji harus dosen lain, bukan pembimbing sendiri.',
        ]);

        $schedule->handle($request->user(), $internship->loadMissing(['student', 'lecturer', 'defenseScore', 'defenseSchedule']), $data);

        return redirect()->route('dospem.sidang')->with('status', 'Jadwal sidang '.rtrim($internship->student->listName(), '.').' tersimpan dan sudah diberitahukan.');
    }
}

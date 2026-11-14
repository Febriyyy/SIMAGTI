<?php

namespace App\Http\Controllers;

use App\Actions\Results\RecordDefense;
use App\Models\Internship;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Kaprodi mengisi nilai sidang KP setelah masa magang selesai (dicatat terpisah dari nilai akhir). */
class DefenseController extends Controller
{
    public function index(Request $request): View
    {
        $internships = Internship::with(['student', 'lecturer', 'company', 'assessment', 'defenseScore'])
            ->where(fn ($q) => $q->where('status', 'selesai')
                ->orWhere(fn ($q) => $q->where('status', 'berjalan')->whereDate('end_date', '<=', today())))
            ->orderBy('end_date')
            ->get();

        [$done, $waiting] = $internships->partition(fn (Internship $i) => $i->defenseScore !== null);

        return view('kaprodi.sidang', ['waiting' => $waiting->values(), 'done' => $done->sortByDesc(fn ($i) => $i->defenseScore->defended_at)->values()]);
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

        return redirect()->route('kaprodi.sidang')->with('status', "Nilai sidang {$internship->student->name} tersimpan.");
    }
}

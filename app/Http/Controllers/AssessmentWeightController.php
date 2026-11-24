<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Support\AssessmentStandard;
use App\Support\Campus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Kaprodi mengatur bobot kategori penilaian akhir dalam persen. Pertanyaan dan skala nilai diatur admin. */
class AssessmentWeightController extends Controller
{
    public function edit(Request $request): View
    {
        // Kaprodi berprodi mengatur bobot prodinya; tanpa prodi mengatur bobot umum
        return view('kaprodi.bobot', [
            'weights' => AssessmentStandard::weights($request->user()->programId()),
            'program' => Campus::program($request->user()->programId()),
            'rubric' => AssessmentStandard::rubric(),
            'scale' => AssessmentStandard::scale(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $rules = [];
        foreach (array_keys(AssessmentStandard::CATEGORIES) as $key) {
            $rules["weights.{$key}"] = ['required', 'numeric', 'between:0,100'];
        }
        $data = $request->validate($rules, [
            'weights.*.required' => 'Isi bobot setiap kategori.',
            'weights.*.between' => 'Bobot harus di antara 0 dan 100.',
        ]);

        $weights = array_map('floatval', $data['weights']);
        if (abs(array_sum($weights) - 100) > 0.001) {
            return back()->withInput()->withErrors(['weights' => 'Total bobot harus 100%. Sekarang '.rtrim(rtrim(number_format(array_sum($weights), 2, ',', ''), '0'), ',').'%.']);
        }

        AssessmentStandard::saveWeights($weights, $request->user()->id, $request->user()->programId());

        $text = collect($weights)->map(fn ($w, $k) => AssessmentStandard::categoryLabel($k).' '.rtrim(rtrim(number_format($w, 2, ',', ''), '0'), ',').'%')->join(', ');
        ActivityLog::record('penilaian.bobot', 'Mengubah bobot penilaian: '.$text);

        return back()->with('status', 'Bobot penilaian disimpan.');
    }
}

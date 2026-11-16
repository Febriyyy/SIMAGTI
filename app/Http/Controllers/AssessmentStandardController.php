<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Support\AssessmentStandard;
use App\Support\Grading;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin: standar nilai kampus. Skala jawaban yang diklik Company Supervisor (label dan nilainya)
 * serta skala huruf mutu. Pertanyaan per kategori dibuat kaprodi (AssessmentWeightController).
 */
class AssessmentStandardController extends Controller
{
    public function index(): View
    {
        return view('admin.penilaian', [
            'scale' => AssessmentStandard::scale(),
            'grades' => Grading::scale(),
        ]);
    }

    public function updateScale(Request $request): RedirectResponse
    {
        $rows = collect($request->input('scale', []))
            ->filter(fn ($row) => filled($row['label'] ?? null) || filled($row['value'] ?? null))
            ->values()
            ->all();
        $request->merge(['scale' => $rows]);

        $data = $request->validate([
            'scale' => ['required', 'array', 'min:2', 'max:7'],
            'scale.*.label' => ['required', 'string', 'max:30'],
            'scale.*.value' => ['required', 'numeric', 'between:0,100', 'distinct'],
        ], [
            'scale.min' => 'Skala minimal 2 pilihan.',
            'scale.max' => 'Skala maksimal 7 pilihan.',
            'scale.*.value.distinct' => 'Nilai skala tidak boleh sama.',
            'scale.*.label.required' => 'Setiap pilihan perlu label.',
            'scale.*.value.required' => 'Setiap pilihan perlu nilai.',
        ]);

        $scale = collect($data['scale'])->map(fn ($r) => ['label' => trim($r['label']), 'value' => (float) $r['value']])->sortByDesc('value')->values()->all();
        AssessmentStandard::saveScale($scale, $request->user()->id);

        ActivityLog::record('penilaian.skala', 'Mengubah skala nilai: '.collect($scale)->map(fn ($r) => $r['label'].' '.(0 + $r['value']))->join(', '));

        return back()->with('status', 'Skala nilai disimpan.');
    }

    /**
     * Huruf mutu diatur bebas oleh admin sesuai standar kampus: nama huruf dan batas bawahnya.
     * Baris kosong diabaikan; urutan mengikuti batas bawah dari tertinggi.
     */
    public function updateGrades(Request $request): RedirectResponse
    {
        $rows = collect($request->input('grades', []))
            ->filter(fn ($row) => is_array($row) && (filled($row['letter'] ?? null) || filled($row['min'] ?? null)))
            ->map(fn ($row) => ['letter' => strtoupper(trim((string) ($row['letter'] ?? ''))), 'min' => $row['min'] ?? null])
            ->values()->all();
        $request->merge(['grades' => $rows]);

        $data = $request->validate([
            'grades' => ['required', 'array', 'min:2', 'max:12'],
            'grades.*.letter' => ['required', 'string', 'max:3', 'regex:/^[A-Z]{1,2}[+-]?$/', 'distinct'],
            'grades.*.min' => ['required', 'numeric', 'between:0,100', 'distinct'],
        ], [
            'grades.min' => 'Isi minimal 2 huruf mutu.',
            'grades.max' => 'Maksimal 12 huruf mutu.',
            'grades.*.letter.required' => 'Setiap baris perlu huruf.',
            'grades.*.letter.regex' => 'Huruf mutu berupa huruf, boleh diakhiri + atau − (mis. A, B+, AB).',
            'grades.*.letter.distinct' => 'Huruf mutu tidak boleh sama.',
            'grades.*.min.required' => 'Setiap huruf perlu batas bawah.',
            'grades.*.min.between' => 'Batas bawah di antara 0 dan 100.',
            'grades.*.min.distinct' => 'Batas bawah setiap huruf tidak boleh sama.',
        ]);

        $scale = collect($data['grades'])->sortByDesc(fn ($r) => (float) $r['min'])
            ->mapWithKeys(fn ($r) => [$r['letter'] => round((float) $r['min'], 2)]);

        Grading::publishScale($scale->all(), $request->user()->id);
        ActivityLog::record('penilaian.huruf', 'Mengubah skala huruf mutu: '.$scale->map(fn ($v, $g) => "{$g} ≥ ".(0 + $v))->join(', '));

        return back()->with('status', 'Skala huruf mutu disimpan. Nilai yang sudah divalidasi tetap memakai skala lamanya.');
    }
}

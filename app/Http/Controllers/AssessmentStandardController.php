<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\AssessmentRubric;
use App\Models\RubricAspect;
use App\Support\AssessmentStandard;
use App\Support\Grading;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Admin: standar penilaian kampus. Pertanyaan per kategori, skala nilai yang diklik Company Supervisor,
 * dan skala huruf mutu. Bila pertanyaan sudah dipakai penilaian, perubahan dibuat sebagai versi baru
 * sehingga penilaian lama tetap utuh.
 */
class AssessmentStandardController extends Controller
{
    public function index(): View
    {
        $rubric = AssessmentStandard::rubric();

        return view('admin.penilaian', [
            'rubric' => $rubric,
            'scale' => AssessmentStandard::scale(),
            'grades' => Grading::scale(),
            'weights' => AssessmentStandard::weights(),
            'used' => $rubric->assessments()->count(),
        ]);
    }

    public function storeQuestion(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'category' => ['required', Rule::in(array_keys(AssessmentStandard::CATEGORIES))],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:300'],
        ], [], ['name' => 'pertanyaan', 'category' => 'kategori']);

        DB::transaction(function () use ($data, $request) {
            $rubric = $this->editableRubric($request->user()->id);
            if ($rubric->aspects()->where('name', $data['name'])->exists()) {
                abort(back()->withInput()->withErrors(['name' => 'Pertanyaan ini sudah ada.']));
            }
            $rubric->aspects()->create($data + ['kind' => 'score', 'weight' => 0, 'sort_order' => (int) $rubric->aspects()->max('sort_order') + 1]);
        });

        ActivityLog::record('penilaian.pertanyaan', 'Menambah pertanyaan '.AssessmentStandard::categoryLabel($data['category']).': '.$data['name']);

        return back()->with('status', 'Pertanyaan ditambahkan.');
    }

    public function updateQuestion(Request $request, RubricAspect $aspect): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:300'],
        ], [], ['name' => 'pertanyaan']);

        DB::transaction(function () use ($aspect, $data, $request) {
            $target = $this->editableAspect($aspect, $request->user()->id);
            $target->update($data);
        });

        ActivityLog::record('penilaian.pertanyaan', 'Mengubah pertanyaan penilaian: '.$data['name']);

        return back()->with('status', 'Pertanyaan diperbarui.');
    }

    public function destroyQuestion(Request $request, RubricAspect $aspect): RedirectResponse
    {
        $name = $aspect->name;
        $remaining = DB::transaction(function () use ($aspect, $request) {
            $target = $this->editableAspect($aspect, $request->user()->id);
            $left = $target->rubric->aspects()->where('category', $target->category)->count();
            if ($left <= 1) {
                return false;
            }
            $target->delete();

            return true;
        });

        if (! $remaining) {
            return back()->withErrors(['name' => 'Setiap kategori minimal punya satu pertanyaan.']);
        }

        ActivityLog::record('penilaian.pertanyaan', 'Menghapus pertanyaan penilaian: '.$name);

        return back()->with('status', 'Pertanyaan dihapus.');
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

    public function updateGrades(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'grades' => ['required', 'array', 'min:2'],
            'grades.*' => ['required', 'numeric', 'between:0,100', 'distinct'],
        ], ['grades.*.distinct' => 'Batas bawah setiap huruf tidak boleh sama.']);

        $grades = collect($data['grades'])->map(fn ($v) => (float) $v)->sortDesc()->all();
        $current = Grading::scale();

        // Huruf yang lebih tinggi harus punya batas lebih tinggi (urutan huruf tetap mengikuti skala yang berlaku)
        $ordered = collect(array_keys($current))->filter(fn ($g) => isset($data['grades'][$g]))->mapWithKeys(fn ($g) => [$g => (float) $data['grades'][$g]]);
        $values = $ordered->values()->all();
        for ($i = 1; $i < count($values); $i++) {
            if ($values[$i] >= $values[$i - 1]) {
                return back()->withInput()->withErrors(['grades' => 'Batas bawah harus menurun dari huruf tertinggi ke terendah.']);
            }
        }

        Grading::publishScale($ordered->all(), $request->user()->id);
        ActivityLog::record('penilaian.huruf', 'Mengubah skala huruf mutu: '.$ordered->map(fn ($v, $g) => "{$g} ≥ ".(0 + $v))->join(', '));

        return back()->with('status', 'Skala huruf mutu disimpan. Nilai yang sudah divalidasi tetap memakai skala lamanya.');
    }

    /** Rubrik yang boleh diubah: rubrik aktif, atau salinannya bila sudah dipakai penilaian. */
    private function editableRubric(int $userId): AssessmentRubric
    {
        $rubric = AssessmentStandard::rubric();
        if (! $rubric->assessments()->exists()) {
            return $rubric;
        }

        $copy = AssessmentRubric::create(['name' => $rubric->name, 'is_active' => true, 'created_by' => $userId]);
        foreach ($rubric->aspects as $aspect) {
            $copy->aspects()->create($aspect->only(['category', 'name', 'description', 'kind', 'weight', 'sort_order']));
        }
        $rubric->update(['is_active' => false]);

        return $copy->load('aspects');
    }

    /** Pertanyaan padanan di rubrik yang boleh diubah. */
    private function editableAspect(RubricAspect $aspect, int $userId): RubricAspect
    {
        $active = AssessmentStandard::rubric();
        abort_unless((int) $aspect->rubric_id === (int) $active->id, 404);

        $rubric = $this->editableRubric($userId);

        return $rubric->id === $active->id
            ? $aspect
            : $rubric->aspects()->where('name', $aspect->name)->where('category', $aspect->category)->firstOrFail();
    }
}

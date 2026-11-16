<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\AssessmentRubric;
use App\Models\RubricAspect;
use App\Support\AssessmentStandard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Kaprodi: rubrik penilaian akhir. Pertanyaan tiap kategori (hard skill, soft skill, attitude) dan bobot kategori
 * dalam persen. Skala jawaban dan huruf mutu diatur admin. Bila pertanyaan sudah dipakai penilaian, perubahan
 * dibuat sebagai versi baru sehingga penilaian lama tetap utuh.
 */
class AssessmentWeightController extends Controller
{
    public function edit(): View
    {
        $rubric = AssessmentStandard::rubric();

        return view('kaprodi.bobot', [
            'weights' => AssessmentStandard::weights(),
            'rubric' => $rubric,
            'scale' => AssessmentStandard::scale(),
            'used' => $rubric->assessments()->count(),
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

        AssessmentStandard::saveWeights($weights, $request->user()->id);

        $text = collect($weights)->map(fn ($w, $k) => AssessmentStandard::categoryLabel($k).' '.rtrim(rtrim(number_format($w, 2, ',', ''), '0'), ',').'%')->join(', ');
        ActivityLog::record('penilaian.bobot', 'Mengubah bobot penilaian: '.$text);

        return back()->with('status', 'Bobot penilaian disimpan.');
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

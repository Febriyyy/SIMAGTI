<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\AssessmentRubric;
use App\Models\Internship;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Kuesioner penilaian akhir milik dosen pembimbing: salinan rubrik kampus (Form Nilai Industri)
 * yang aspek, bobot, dan pertanyaan isiannya bisa diubah, lalu dipakai untuk mahasiswa bimbingannya.
 */
class QuestionnaireController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        return view('kuesioner.index', [
            'default' => AssessmentRubric::active()?->load('aspects'),
            'mine' => AssessmentRubric::withCount(['aspects', 'assessments'])->where('owner_id', $user->id)->latest('id')->get(),
            'students' => Internship::with(['student', 'assessment', 'rubric'])->where('lecturer_id', $user->id)
                ->where('status', 'berjalan')->orderBy('end_date')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'source_id' => ['nullable', 'integer'],
        ], [], ['name' => 'nama kuesioner']);

        $source = filled($data['source_id'] ?? null)
            ? AssessmentRubric::where('id', $data['source_id'])->where(fn ($q) => $q->where('owner_id', $user->id)->orWhereNull('owner_id'))->firstOrFail()
            : AssessmentRubric::active();
        abort_unless($source, 404);

        $rubric = DB::transaction(function () use ($user, $data, $source) {
            $rubric = AssessmentRubric::create(['name' => $data['name'], 'is_active' => false, 'owner_id' => $user->id, 'created_by' => $user->id]);
            foreach ($source->aspects()->orderBy('sort_order')->get() as $aspect) {
                $rubric->aspects()->create($aspect->only(['name', 'description', 'kind', 'weight', 'sort_order']));
            }

            return $rubric;
        });

        ActivityLog::record('kuesioner.buat', "Membuat kuesioner \"{$rubric->name}\"", $rubric);

        return redirect()->route('kuesioner.edit', $rubric)->with('status', 'Kuesioner dibuat dari salinan. Silakan sesuaikan.');
    }

    public function edit(Request $request, AssessmentRubric $rubric): View
    {
        $this->own($request, $rubric);

        return view('kuesioner.edit', [
            'rubric' => $rubric->load(['aspects' => fn ($q) => $q->orderBy('sort_order')]),
            'locked' => $rubric->assessments()->exists(),
        ]);
    }

    public function update(Request $request, AssessmentRubric $rubric): RedirectResponse
    {
        $this->own($request, $rubric);
        if ($rubric->assessments()->exists()) {
            throw ValidationException::withMessages(['aspects' => 'Kuesioner ini sudah dipakai menilai mahasiswa. Buat salinan untuk mengubahnya.']);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'aspects' => ['required', 'array', 'min:1', 'max:20'],
            'aspects.*.name' => ['nullable', 'string', 'max:100'],
            'aspects.*.description' => ['nullable', 'string', 'max:300'],
            'aspects.*.kind' => ['required', Rule::in(['score', 'text'])],
            'aspects.*.weight' => ['nullable', 'numeric', 'between:0,100'],
        ], [], ['name' => 'nama kuesioner']);

        $rows = collect($data['aspects'])->filter(fn ($a) => filled($a['name'] ?? null))->values();
        $scoreRows = $rows->where('kind', 'score');
        if ($scoreRows->isEmpty()) {
            throw ValidationException::withMessages(['aspects' => 'Kuesioner perlu minimal satu aspek bernilai.']);
        }
        if (abs($scoreRows->sum(fn ($a) => (float) ($a['weight'] ?? 0)) - 100) > 0.001) {
            throw ValidationException::withMessages(['aspects' => 'Total bobot aspek bernilai harus 100%. Sekarang '.rtrim(rtrim(number_format($scoreRows->sum(fn ($a) => (float) ($a['weight'] ?? 0)), 2, ',', ''), '0'), ',').'%.']);
        }
        if ($rows->pluck('name')->map(fn ($n) => mb_strtolower(trim($n)))->duplicates()->isNotEmpty()) {
            throw ValidationException::withMessages(['aspects' => 'Nama aspek atau pertanyaan tidak boleh kembar.']);
        }

        DB::transaction(function () use ($rubric, $data, $rows) {
            $rubric->update(['name' => $data['name']]);
            $rubric->aspects()->delete();
            foreach ($rows as $i => $a) {
                $rubric->aspects()->create([
                    'name' => trim($a['name']),
                    'description' => filled($a['description'] ?? null) ? trim($a['description']) : null,
                    'kind' => $a['kind'],
                    'weight' => $a['kind'] === 'score' ? (float) $a['weight'] : 0,
                    'sort_order' => $i,
                ]);
            }
        });

        ActivityLog::record('kuesioner.ubah', "Mengubah kuesioner \"{$rubric->name}\"", $rubric);

        return redirect()->route('kuesioner.index')->with('status', 'Kuesioner tersimpan.');
    }

    /** Pilih kuesioner per mahasiswa bimbingan (selama penilaian akhirnya belum dimulai). */
    public function assign(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate(['assign' => ['required', 'array'], 'assign.*' => ['nullable', 'integer']]);
        $own = AssessmentRubric::where('owner_id', $user->id)->pluck('id')->all();
        $changed = 0;

        foreach ($data['assign'] as $internshipId => $rubricId) {
            $internship = Internship::with('assessment')->where('lecturer_id', $user->id)->find($internshipId);
            if (! $internship || $internship->assessment) {
                continue; // bukan bimbingan atau penilaian sudah dimulai: kuesioner tidak bisa diganti
            }
            $rubricId = filled($rubricId) && in_array((int) $rubricId, $own, true) ? (int) $rubricId : null;
            if ((int) $internship->assessment_rubric_id !== (int) $rubricId) {
                $internship->update(['assessment_rubric_id' => $rubricId]);
                $changed++;
            }
        }

        return back()->with('status', $changed ? "Kuesioner untuk {$changed} mahasiswa diperbarui." : 'Tidak ada perubahan.');
    }

    private function own(Request $request, AssessmentRubric $rubric): void
    {
        abort_unless((int) $rubric->owner_id === (int) $request->user()->id, 403);
    }
}

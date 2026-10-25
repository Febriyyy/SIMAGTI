<?php

namespace App\Http\Requests\Assessments;

use App\Models\Assessment;
use App\Models\Internship;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SaveAssessmentRequest extends FormRequest
{
    public function internship(): Internship
    {
        return $this->route('internship');
    }

    public function authorize(): bool
    {
        return Gate::forUser($this->user())->authorize('assess', $this->internship())->allowed();
    }

    public function rules(): array
    {
        return [
            'aksi' => ['required', Rule::in(['simpan', 'kirim'])],
            'scores' => ['required', 'array'],
            'scores.*.score' => ['nullable', 'numeric', 'between:0,100'],
            'scores.*.note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'scores.*.score.numeric' => 'Nilai harus berupa angka.',
            'scores.*.score.between' => 'Nilai harus di antara 0 dan 100.',
            'scores.*.note.max' => 'Catatan atau jawaban maksimal 1000 karakter.',
        ];
    }

    /** Hanya aspek dari rubrik yang dipakai; saat dikirim semua aspek wajib terisi. */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $rubric = Assessment::rubricFor($this->internship());
                $aspects = $rubric ? $rubric->aspects : collect();
                $aspectIds = $aspects->pluck('id')->map(fn ($id) => (string) $id)->all();
                $scores = (array) $this->input('scores', []);

                foreach (array_keys($scores) as $id) {
                    if (! in_array((string) $id, $aspectIds, true)) {
                        $validator->errors()->add('scores', 'Ada aspek penilaian yang tidak dikenal.');

                        return;
                    }
                }

                if ($this->input('aksi') === 'kirim') {
                    foreach ($aspects as $aspect) {
                        $row = $scores[(string) $aspect->id] ?? $scores[$aspect->id] ?? [];
                        $field = $aspect->isText() ? 'note' : 'score';
                        if (! isset($row[$field]) || trim((string) $row[$field]) === '') {
                            $validator->errors()->add('scores', 'Semua aspek dan pertanyaan harus diisi sebelum dikirim ke dosen pembimbing.');

                            return;
                        }
                    }
                }
            },
        ];
    }

    /** @return array<int, array{score: string|null, note: string|null}> aspek yang diberi nilai atau pertanyaan yang dijawab */
    public function scores(): array
    {
        return collect($this->validated('scores'))
            ->filter(fn ($row) => (isset($row['score']) && $row['score'] !== '') || filled($row['note'] ?? null))
            ->map(fn ($row) => ['score' => isset($row['score']) && $row['score'] !== '' ? $row['score'] : null, 'note' => $row['note'] ?? null])
            ->all();
    }

    public function submitting(): bool
    {
        return $this->validated('aksi') === 'kirim';
    }
}

<?php

namespace App\Http\Requests\Assessments;

use App\Models\Assessment;
use App\Models\Internship;
use App\Support\AssessmentStandard;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Penilaian akhir Company Supervisor: setiap pertanyaan dijawab dengan memilih satu nilai dari skala standar kampus.
 */
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
        $values = array_map(fn ($row) => (string) (0 + $row['value']), AssessmentStandard::scale());

        return [
            'aksi' => ['required', Rule::in(['simpan', 'kirim'])],
            'scores' => ['nullable', 'array'],
            'scores.*' => ['nullable', Rule::in($values)],
            'comment' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'scores.*.in' => 'Pilih salah satu nilai dari skala yang tersedia.',
            'comment.max' => 'Catatan maksimal 2000 karakter.',
        ];
    }

    /** Hanya pertanyaan dari standar penilaian; saat dikirim semua pertanyaan wajib dijawab dan bobot harus 100%. */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $aspects = Assessment::rubricFor($this->internship())->aspects->where('kind', '!=', 'text');
                $ids = $aspects->pluck('id')->map(fn ($id) => (string) $id)->all();
                $scores = array_filter((array) $this->input('scores', []), fn ($v) => $v !== null && $v !== '');

                foreach (array_keys($scores) as $id) {
                    if (! in_array((string) $id, $ids, true)) {
                        $validator->errors()->add('scores', 'Ada pertanyaan penilaian yang tidak dikenal.');

                        return;
                    }
                }

                if ($this->input('aksi') === 'kirim') {
                    $missing = $aspects->reject(fn ($a) => isset($scores[$a->id]) || isset($scores[(string) $a->id]))->count();
                    if ($missing > 0) {
                        $validator->errors()->add('scores', "Masih ada {$missing} pertanyaan yang belum dinilai.");
                    }
                    if (abs(AssessmentStandard::totalWeight() - 100) > 0.001) {
                        $validator->errors()->add('scores', 'Bobot penilaian belum 100%. Hubungi kaprodi.');
                    }
                }
            },
        ];
    }

    /** @return array<int, float> rubric_aspect_id => nilai yang dipilih */
    public function scores(): array
    {
        return collect($this->validated('scores') ?? [])
            ->filter(fn ($v) => $v !== null && $v !== '')
            ->map(fn ($v) => (float) $v)
            ->all();
    }

    public function comment(): ?string
    {
        return filled($this->validated('comment')) ? trim($this->validated('comment')) : null;
    }

    public function submitting(): bool
    {
        return $this->validated('aksi') === 'kirim';
    }
}

<?php

namespace App\Http\Requests\Assessments;

use App\Models\Internship;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ReviewAssessmentRequest extends FormRequest
{
    public function internship(): Internship
    {
        return $this->route('internship');
    }

    public function authorize(): bool
    {
        return Gate::forUser($this->user())->authorize('validateAssessment', $this->internship())->allowed();
    }

    public function rules(): array
    {
        return [
            'keputusan' => ['required', Rule::in(['validasi', 'kembalikan'])],
            'catatan' => ['nullable', 'required_if:keputusan,kembalikan', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'catatan.required_if' => 'Tulis alasan agar penilai tahu apa yang perlu diperbaiki.',
            'catatan.max' => 'Catatan maksimal 500 karakter.',
        ];
    }
}

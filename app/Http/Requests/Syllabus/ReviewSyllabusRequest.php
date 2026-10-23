<?php

namespace App\Http\Requests\Syllabus;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ReviewSyllabusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::forUser($this->user())->authorize('reviewSyllabus', $this->route('internship'))->allowed();
    }

    public function rules(): array
    {
        return [
            'keputusan' => ['required', Rule::in(['setujui', 'revisi'])],
            'catatan' => ['nullable', 'string', 'max:1000'],
        ];
    }
}

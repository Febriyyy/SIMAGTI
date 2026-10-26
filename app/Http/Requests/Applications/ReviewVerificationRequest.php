<?php

namespace App\Http\Requests\Applications;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class ReviewVerificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::forUser($this->user())->authorize('verifyAsTu', $this->route('internship'))->allowed();
    }

    public function rules(): array
    {
        return [
            'decision' => ['required', 'in:approve,reject'],
            'note' => ['required_if:decision,reject', 'nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return ['note.required_if' => 'Alasan penolakan wajib diisi.'];
    }

    public function approved(): bool
    {
        return $this->validated('decision') === 'approve';
    }
}

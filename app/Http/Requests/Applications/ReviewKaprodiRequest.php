<?php

namespace App\Http\Requests\Applications;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ReviewKaprodiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::forUser($this->user())->authorize('reviewAsKaprodi', $this->route('internship'))->allowed();
    }

    public function rules(): array
    {
        return [
            'decision' => ['required', 'in:approve,reject'],
            'lecturer_id' => [
                'required_if:decision,approve',
                'nullable',
                Rule::exists('users', 'id')->where(fn ($q) => $q->where('role', 'dospem')->where('is_active', true)),
            ],
            'note' => ['required_if:decision,reject', 'nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'lecturer_id.required_if' => 'Pilih dosen pembimbing.',
            'lecturer_id.exists' => 'Dosen pembimbing tidak valid.',
            'note.required_if' => 'Alasan penolakan wajib diisi.',
        ];
    }

    public function approved(): bool
    {
        return $this->validated('decision') === 'approve';
    }
}

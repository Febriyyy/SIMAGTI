<?php

namespace App\Http\Requests\Supervisors;

use Illuminate\Foundation\Http\FormRequest;

/** Satu kelompok aktivasi: perusahaan + email supervisor. Hak akses per penempatan dicek di Action. */
class ActivationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role === 'tu';
    }

    public function rules(): array
    {
        return [
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'email' => ['required', 'email', 'max:150'],
            'new_email' => [$this->isMethod('patch') ? 'required' : 'nullable', 'email', 'max:150', 'different:email'],
        ];
    }

    public function attributes(): array
    {
        return ['email' => 'email supervisor', 'new_email' => 'email baru'];
    }
}

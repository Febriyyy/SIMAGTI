<?php

namespace App\Http\Requests\Students;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::forUser($this->user())->authorize('manageStudents', User::class)->allowed();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')],
            'nim' => ['required', 'string', 'max:20', Rule::unique('users', 'nim')],
            'study_program_id' => ['required', Rule::exists('study_programs', 'id')->whereNull('deleted_at')],
            'entry_year' => ['nullable', 'integer', 'min:2000', 'max:'.((int) date('Y') + 1)],
            'gpa' => ['nullable', 'numeric', 'between:0,4'],
            'password' => ['required', 'string', 'min:8'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nama',
            'nim' => 'NIM',
            'study_program_id' => 'program studi',
            'entry_year' => 'angkatan',
            'gpa' => 'IPK',
            'password' => 'kata sandi awal',
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'Email ini sudah terdaftar.',
            'nim.unique' => 'NIM ini sudah terdaftar.',
        ];
    }
}

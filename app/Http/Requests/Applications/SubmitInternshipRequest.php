<?php

namespace App\Http\Requests\Applications;

use App\Models\Internship;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class SubmitInternshipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::forUser($this->user())->authorize('submitIndependent', Internship::class)->allowed();
    }

    public function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'max:150'],
            'industry' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'company_email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:20'],
            'website' => ['nullable', 'url:http,https', 'max:200'],
            'division_name' => ['nullable', 'string', 'max:100'],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'mentor_email' => ['nullable', 'email', 'max:150'],
            'supervisor_email' => ['nullable', 'email', 'max:150', 'different:mentor_email'],
        ];
    }

    public function attributes(): array
    {
        return [
            'company_name' => 'nama perusahaan',
            'company_email' => 'email perusahaan',
            'start_date' => 'tanggal mulai',
            'end_date' => 'tanggal selesai',
            'mentor_email' => 'email mentor',
            'supervisor_email' => 'email ketua divisi',
        ];
    }
}

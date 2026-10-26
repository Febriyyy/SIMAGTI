<?php

namespace App\Http\Requests\Applications;

use App\Models\Company;
use App\Models\Internship;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class SubmitInternshipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::forUser($this->user())->authorize('submitIndependent', Internship::class)->allowed();
    }

    /** Bidang usaha dipilih dari daftar; "Lainnya" diisi sendiri. */
    protected function prepareForValidation(): void
    {
        if ($this->input('industry') === Company::INDUSTRY_OTHER) {
            $this->merge(['industry' => trim((string) $this->input('industry_other')) ?: null, 'industry_choice' => Company::INDUSTRY_OTHER]);
        }

        // Website opsional: "perusahaan.co.id" diterima dan dilengkapi https://
        $site = trim((string) $this->input('website'));
        if ($site !== '' && ! preg_match('#^https?://#i', $site)) {
            $this->merge(['website' => 'https://'.$site]);
        }
    }

    public function rules(): array
    {
        return [
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after:start_date'],

            'company_name' => ['required', 'string', 'max:150'],
            'industry' => ['required', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'company_email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:20'],
            'website' => ['nullable', 'url:http,https', 'max:200'],
            'division_name' => ['nullable', 'string', 'max:100'],
            'position' => ['required', 'string', 'max:100'],
            'supervisor_name' => ['required', 'string', 'max:150'],
            'supervisor_title' => ['nullable', 'string', 'max:100'],
            'supervisor_email' => ['required', 'email', 'max:150'],
            'supervisor_phone' => ['nullable', 'string', 'max:30'],
        ];
    }

    public function messages(): array
    {
        return [
            'industry.required' => $this->input('industry_choice') === Company::INDUSTRY_OTHER
                ? 'Tulis bidang usaha perusahaan.'
                : 'Pilih bidang usaha perusahaan.',
            'website.url' => 'Alamat website tidak valid. Kosongkan bila perusahaan tidak punya website.',
        ];
    }

    public function attributes(): array
    {
        return [
            'company_name' => 'nama perusahaan',
            'industry' => 'bidang usaha',
            'company_email' => 'email perusahaan',
            'division_name' => 'divisi',
            'position' => 'posisi',
            'start_date' => 'tanggal mulai',
            'end_date' => 'tanggal selesai',
            'supervisor_name' => 'nama Company Supervisor',
            'supervisor_title' => 'jabatan Company Supervisor',
            'supervisor_email' => 'email Company Supervisor',
            'supervisor_phone' => 'nomor HP Company Supervisor',
        ];
    }
}

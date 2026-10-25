<?php

namespace App\Http\Requests\Companies;

use App\Models\Company;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/** Dipakai untuk menambah dan mengubah perusahaan. Saat mengubah, rute membawa {company}. */
class SaveCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::forUser($this->user())->authorize('manage', Company::class)->allowed();
    }

    public function rules(): array
    {
        /** @var Company|null $company */
        $company = $this->route('company');

        return [
            'name' => [
                'required', 'string', 'max:150',
                Rule::unique('companies', 'name')->ignore($company?->id)->withoutTrashed(),
            ],
            'industry' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:20'],
            'website' => ['nullable', 'url:http,https', 'max:200'],
            'divisions' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function attributes(): array
    {
        return ['name' => 'nama perusahaan'];
    }

    public function messages(): array
    {
        return ['name.unique' => 'Perusahaan dengan nama ini sudah terdaftar.'];
    }
}

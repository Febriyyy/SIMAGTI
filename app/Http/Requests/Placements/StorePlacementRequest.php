<?php

namespace App\Http\Requests\Placements;

use App\Models\CompanyStaffProfile;
use App\Models\Internship;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StorePlacementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::forUser($this->user())->authorize('create', Internship::class)->allowed();
    }

    public function rules(): array
    {
        $companyId = $this->integer('company_id');

        return [
            'student_id' => [
                'required',
                Rule::exists('users', 'id')->where(fn ($q) => $q->where('role', 'mahasiswa')->where('is_active', true)->whereNull('deleted_at')),
                // Aturan bisnis: satu mahasiswa hanya boleh punya satu penempatan aktif.
                function (string $attribute, mixed $value, \Closure $fail) {
                    $hasActive = Internship::where('student_id', $value)
                        ->whereIn('status', Internship::ACTIVE_STATUSES)
                        ->exists();

                    if ($hasActive) {
                        $fail('Mahasiswa ini masih memiliki penempatan atau pengajuan KP yang aktif.');
                    }
                },
            ],
            'company_id' => [
                'required',
                Rule::exists('companies', 'id')
                    ->whereIn('status', ['mitra', 'mandiri_terverifikasi'])
                    ->whereNull('deleted_at'),
            ],
            'division_id' => [
                'nullable',
                Rule::exists('company_divisions', 'id')->where('company_id', $companyId),
            ],
            'lecturer_id' => [
                'required',
                Rule::exists('users', 'id')->where(fn ($q) => $q->where('role', 'dospem')->where('is_active', true)->whereNull('deleted_at')),
            ],
            'supervisor_id' => ['required', $this->supervisorOfCompany($companyId)],
            'position' => ['nullable', 'string', 'max:100'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
        ];
    }

    /** Company Supervisor harus terdaftar sebagai staf di perusahaan yang dipilih. */
    private function supervisorOfCompany(int $companyId): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) use ($companyId) {
            $valid = CompanyStaffProfile::where('user_id', $value)
                ->where('company_id', $companyId)
                ->whereHas('user', fn ($q) => $q->where('role', 'supervisor')->where('is_active', true))
                ->exists();

            if (! $valid) {
                $fail('Company Supervisor harus staf dari perusahaan yang dipilih.');
            }
        };
    }

    public function attributes(): array
    {
        return [
            'student_id' => 'mahasiswa',
            'company_id' => 'perusahaan',
            'division_id' => 'divisi',
            'lecturer_id' => 'dosen pembimbing',
            'supervisor_id' => 'Company Supervisor',
            'position' => 'posisi',
            'start_date' => 'tanggal mulai',
            'end_date' => 'tanggal selesai',
        ];
    }
}

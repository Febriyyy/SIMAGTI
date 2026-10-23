<?php

namespace App\Http\Requests\Applications;

use App\Models\Internship;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class SubmitInternshipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::forUser($this->user())->authorize('submitIndependent', Internship::class)->allowed();
    }

    public function rules(): array
    {
        $program = (string) $this->input('program');
        $types = array_keys(Internship::PROGRAMS[$program][1] ?? []);
        $magang = $program === 'magang';

        return [
            'program' => ['required', Rule::in(array_keys(Internship::PROGRAMS))],
            'program_type' => ['required', Rule::in($types)],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after:start_date'],

            // Magang
            'company_name' => [Rule::requiredIf($magang), 'nullable', 'string', 'max:150'],
            'industry' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'company_email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:20'],
            'website' => ['nullable', 'url:http,https', 'max:200'],
            'division_name' => ['nullable', 'string', 'max:100'],
            'position' => [Rule::requiredIf($magang), 'nullable', 'string', 'max:100'],
            'supervisor_name' => [Rule::requiredIf($magang), 'nullable', 'string', 'max:150'],
            'supervisor_title' => ['nullable', 'string', 'max:100'],
            'supervisor_email' => [Rule::requiredIf($magang), 'nullable', 'email', 'max:150'],
            'supervisor_phone' => ['nullable', 'string', 'max:30'],

            // Seminar
            'topic' => [Rule::requiredIf($program === 'seminar'), 'nullable', 'string', 'max:200'],
            'organizer' => ['nullable', 'string', 'max:150'],
        ];
    }

    public function attributes(): array
    {
        return [
            'program' => 'jalur KP',
            'program_type' => 'jenis KP',
            'company_name' => 'nama perusahaan',
            'company_email' => 'email perusahaan',
            'division_name' => 'divisi',
            'position' => 'posisi',
            'start_date' => 'tanggal mulai',
            'end_date' => 'tanggal selesai',
            'supervisor_name' => 'nama Company Supervisor',
            'supervisor_title' => 'jabatan Company Supervisor',
            'supervisor_email' => 'email Company Supervisor',
            'supervisor_phone' => 'nomor HP Company Supervisor',
            'topic' => 'judul kegiatan seminar',
            'organizer' => 'penyelenggara',
        ];
    }
}

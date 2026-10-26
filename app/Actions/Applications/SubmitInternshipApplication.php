<?php

namespace App\Actions\Applications;

use App\Models\AcademicPeriod;
use App\Models\ActivityLog;
use App\Models\Company;
use App\Models\CompanyDivision;
use App\Models\Internship;
use App\Models\InternshipApproval;
use App\Models\User;
use App\Support\Campus;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Mahasiswa yang belum mendapat tempat dari kampus mengajukan KP mandiri (satu jenis KP, tanpa jalur MBKM/seminar):
 * perusahaan baru menunggu verifikasi TU Fakultas, lalu persetujuan kaprodi.
 * Data Company Supervisor disimpan di penempatan; akunnya diaktifkan TU Fakultas setelah disetujui.
 */
class SubmitInternshipApplication
{
    /**
     * @param  array<string, mixed>  $data  sudah divalidasi oleh SubmitInternshipRequest
     */
    public function handle(User $student, array $data): Internship
    {
        $this->checkSemester($student);

        return DB::transaction(function () use ($student, $data) {
            // Perusahaan yang sudah tercatat (nama sama) dipakai ulang, supaya mahasiswa satu surat pengajuan
            // berada di perusahaan yang sama dan TU cukup mengaktifkan supervisornya sekali.
            $company = Company::whereRaw('LOWER(name) = ?', [mb_strtolower(trim($data['company_name']))])->first()
                ?? Company::create([
                    'name' => trim($data['company_name']),
                    'industry' => $data['industry'] ?? null,
                    'address' => $data['address'] ?? null,
                    'city' => $data['city'] ?? null,
                    'email' => $data['company_email'] ?? null,
                    'phone' => $data['phone'] ?? null,
                    'website' => $data['website'] ?? null,
                    'status' => 'mandiri_menunggu',
                    'created_by' => $student->id,
                ]);

            $division = filled($data['division_name'] ?? null)
                ? CompanyDivision::firstOrCreate(['company_id' => $company->id, 'name' => trim($data['division_name'])])
                : null;

            $internship = Internship::create([
                'student_id' => $student->id,
                'company_id' => $company->id,
                'division_id' => $division?->id,
                'position' => $data['position'] ?? null,
                'supervisor_name' => $data['supervisor_name'],
                'supervisor_title' => $data['supervisor_title'] ?? null,
                'supervisor_email' => $data['supervisor_email'],
                'supervisor_phone' => $data['supervisor_phone'] ?? null,
                'academic_period_id' => AcademicPeriod::current()?->id,
                'track' => 'mandiri',
                'status' => 'diajukan',
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
            ]);

            $steps = [InternshipApproval::STEP_VERIFY, InternshipApproval::STEP_KAPRODI];

            // Perusahaan yang sudah diverifikasi TU (mitra) tidak perlu diverifikasi ulang.
            $verified = in_array($company->status, ['mitra', 'mandiri_terverifikasi'], true);

            foreach ($steps as $step) {
                $auto = $step === InternshipApproval::STEP_VERIFY && $verified;
                InternshipApproval::create([
                    'internship_id' => $internship->id,
                    'step' => $step,
                    'status' => $auto ? 'approved' : 'pending',
                    'reviewed_at' => $auto ? now() : null,
                    'note' => $auto ? 'Perusahaan sudah terverifikasi sebelumnya.' : null,
                ]);
            }

            ActivityLog::record(
                'kp.ajukan',
                "{$student->name} mengajukan KP di {$internship->placeName()}",
                $internship
            );

            return $internship;
        });
    }

    /** Syarat Panduan KP: minimal semester V. */
    private function checkSemester(User $student): void
    {
        $semester = $student->studentProfile?->semester();

        if ($semester === null) {
            return; // angkatan belum diisi kampus: tidak bisa dicek, biarkan kaprodi yang menilai
        }

        $min = (int) Campus::rule('min_semester');
        if ($semester < $min) {
            throw ValidationException::withMessages([
                'start_date' => "KP dapat diambil mulai semester {$min}. Saat ini Anda semester {$semester}.",
            ]);
        }
    }
}

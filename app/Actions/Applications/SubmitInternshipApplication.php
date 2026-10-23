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
 * Mahasiswa mengajukan KP, memilih jalur:
 *  - Magang (KP Industri / MBKM Mandiri): perusahaan baru menunggu verifikasi biro, lalu persetujuan kaprodi.
 *    Data Company Supervisor disimpan di penempatan; akunnya diaktifkan TU Fakultas setelah disetujui.
 *  - Seminar (Pelaksana / Peserta): tanpa perusahaan, langsung menunggu persetujuan kaprodi.
 */
class SubmitInternshipApplication
{
    /**
     * @param  array<string, mixed>  $data  sudah divalidasi oleh SubmitInternshipRequest
     */
    public function handle(User $student, array $data): Internship
    {
        $this->checkSemester($student, $data['program'], $data['program_type']);

        return DB::transaction(function () use ($student, $data) {
            $isSeminar = $data['program'] === 'seminar';
            $company = null;
            $division = null;

            if (! $isSeminar) {
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
            }

            $internship = Internship::create([
                'student_id' => $student->id,
                'company_id' => $company?->id,
                'division_id' => $division?->id,
                'position' => $isSeminar ? null : ($data['position'] ?? null),
                'topic' => $isSeminar ? $data['topic'] : null,
                'organizer' => $isSeminar ? ($data['organizer'] ?? null) : null,
                'supervisor_name' => $isSeminar ? null : $data['supervisor_name'],
                'supervisor_title' => $isSeminar ? null : ($data['supervisor_title'] ?? null),
                'supervisor_email' => $isSeminar ? null : $data['supervisor_email'],
                'supervisor_phone' => $isSeminar ? null : ($data['supervisor_phone'] ?? null),
                'academic_period_id' => AcademicPeriod::current()?->id,
                'track' => 'mandiri',
                'program' => $data['program'],
                'program_type' => $data['program_type'],
                'status' => 'diajukan',
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
            ]);

            $steps = $isSeminar
                ? [InternshipApproval::STEP_KAPRODI]
                : [InternshipApproval::STEP_BIRO, InternshipApproval::STEP_KAPRODI];

            // Perusahaan yang sudah diverifikasi biro (mitra) tidak perlu diverifikasi ulang.
            $verified = $company && in_array($company->status, ['mitra', 'mandiri_terverifikasi'], true);

            foreach ($steps as $step) {
                $auto = $step === InternshipApproval::STEP_BIRO && $verified;
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
                "{$student->name} mengajukan KP {$internship->programTypeLabel()}: {$internship->placeName()}",
                $internship
            );

            return $internship;
        });
    }

    /** Syarat Panduan KP: minimal semester V. MBKM Mandiri: semester 5 sampai 7 (Pakta Integritas). */
    private function checkSemester(User $student, string $program, string $type): void
    {
        $semester = $student->studentProfile?->semester();

        if ($semester === null) {
            return; // angkatan belum diisi kampus: tidak bisa dicek, biarkan kaprodi yang menilai
        }

        $min = (int) Campus::rule('min_semester');
        if ($semester < $min) {
            throw ValidationException::withMessages([
                'program' => "KP dapat diambil mulai semester {$min}. Saat ini Anda semester {$semester}.",
            ]);
        }

        if ($program === 'magang' && $type === 'mbkm') {
            $from = (int) Campus::rule('mbkm_min_semester');
            $to = (int) Campus::rule('mbkm_max_semester');

            if ($semester < $from || $semester > $to) {
                throw ValidationException::withMessages([
                    'program_type' => "Magang MBKM Mandiri untuk semester {$from} sampai {$to}. Saat ini Anda semester {$semester}.",
                ]);
            }
        }
    }
}

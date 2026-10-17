<?php

namespace App\Actions\Applications;

use App\Models\AcademicPeriod;
use App\Models\ActivityLog;
use App\Models\Company;
use App\Models\CompanyDivision;
use App\Models\Internship;
use App\Models\InternshipApproval;
use App\Models\InternshipStaff;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Mahasiswa mengajukan KP di tempat yang dicari sendiri.
 *
 * Hasilnya, dalam satu transaksi:
 *  - perusahaan baru (status "mandiri_menunggu"),
 *  - penempatan berstatus "diajukan" (jalur mandiri),
 *  - dua langkah persetujuan yang menunggu: verifikasi biro lalu persetujuan kaprodi,
 *  - undangan untuk mentor / ketua divisi (jika email diisi).
 */
class SubmitIndependentInternship
{
    /**
     * @param  array<string, mixed>  $data  sudah divalidasi oleh SubmitInternshipRequest
     */
    public function handle(User $student, array $data): Internship
    {
        return DB::transaction(function () use ($student, $data) {
            $company = Company::create([
                'name' => $data['company_name'],
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
                ? CompanyDivision::create(['company_id' => $company->id, 'name' => $data['division_name']])
                : null;

            $internship = Internship::create([
                'student_id' => $student->id,
                'company_id' => $company->id,
                'division_id' => $division?->id,
                'academic_period_id' => AcademicPeriod::current()?->id,
                'track' => 'mandiri',
                'status' => 'diajukan',
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'notes' => 'Tempat KP dicari sendiri oleh mahasiswa.',
            ]);

            foreach ([InternshipApproval::STEP_BIRO, InternshipApproval::STEP_KAPRODI] as $step) {
                InternshipApproval::create([
                    'internship_id' => $internship->id,
                    'step' => $step,
                    'status' => 'pending',
                ]);
            }

            $this->invite($internship, $company, $student, $data['mentor_email'] ?? null, InternshipStaff::ROLE_MENTOR);
            $this->invite($internship, $company, $student, $data['supervisor_email'] ?? null, InternshipStaff::ROLE_SUPERVISOR);

            ActivityLog::record(
                'kp.ajukan',
                "{$student->name} mengajukan KP mandiri di {$company->name}",
                $internship
            );

            return $internship;
        });
    }

    /**
     * Akun yang sudah ada dengan peran yang sama langsung dipasang sebagai staf penempatan.
     * Selain itu dibuatkan undangan (link aktivasi yang berlaku 14 hari).
     */
    private function invite(Internship $internship, Company $company, User $student, ?string $email, string $role): void
    {
        if (! filled($email)) {
            return;
        }

        $email = mb_strtolower(trim($email));
        $existing = User::where('email', $email)->first();

        if ($existing) {
            if ($existing->role !== $role) {
                throw ValidationException::withMessages([
                    $role === InternshipStaff::ROLE_MENTOR ? 'mentor_email' : 'supervisor_email'
                        => "Email {$email} sudah terdaftar dengan peran lain.",
                ]);
            }

            $internship->assignStaff($existing, $role);

            return;
        }

        Invitation::create([
            'email' => $email,
            'role' => $role,
            'company_id' => $company->id,
            'internship_id' => $internship->id,
            'invited_by' => $student->id,
            'expires_at' => now()->addDays(14),
        ]);
    }
}

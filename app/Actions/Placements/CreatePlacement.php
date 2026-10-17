<?php

namespace App\Actions\Placements;

use App\Models\AcademicPeriod;
use App\Models\ActivityLog;
use App\Models\Internship;
use App\Models\InternshipStaff;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Kaprodi menempatkan mahasiswa di perusahaan mitra (jalur lowongan kampus).
 * Penempatan langsung aktif dengan dosen pembimbing, mentor, dan (opsional) ketua divisi.
 */
class CreatePlacement
{
    /**
     * @param  array<string, mixed>  $data  sudah divalidasi oleh StorePlacementRequest
     */
    public function handle(User $actor, array $data): Internship
    {
        return DB::transaction(function () use ($data) {
            $internship = Internship::create([
                'student_id' => $data['student_id'],
                'company_id' => $data['company_id'],
                'division_id' => $data['division_id'] ?? null,
                'lecturer_id' => $data['lecturer_id'],
                'academic_period_id' => AcademicPeriod::current()?->id,
                'track' => 'lowongan',
                'status' => 'berjalan',
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
            ]);

            $internship->assignStaff((int) $data['mentor_id'], InternshipStaff::ROLE_MENTOR);

            if (filled($data['supervisor_id'] ?? null)) {
                $internship->assignStaff((int) $data['supervisor_id'], InternshipStaff::ROLE_SUPERVISOR);
            }

            $internship->load('student', 'company');

            ActivityLog::record(
                'penempatan.buat',
                "Menempatkan {$internship->student->name} di {$internship->company->name}",
                $internship
            );

            return $internship;
        });
    }
}

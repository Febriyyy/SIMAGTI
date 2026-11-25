<?php

namespace App\Actions\Students;

use App\Models\ActivityLog;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/** Kaprodi mendaftarkan akun mahasiswa beserta profil akademiknya. */
class CreateStudent
{
    /**
     * @param  array<string, mixed>  $data  sudah divalidasi oleh StoreStudentRequest
     */
    public function handle(User $actor, array $data, bool $log = true): User
    {
        return DB::transaction(function () use ($data, $log) {
            $student = User::create([
                'name' => $data['name'],
                'email' => mb_strtolower($data['email']),
                'password' => $data['password'],
                'role' => 'mahasiswa',
                'nim' => $data['nim'],
                'is_active' => true,
                'must_change_password' => (bool) ($data['must_change_password'] ?? false),
            ]);

            StudentProfile::create([
                'user_id' => $student->id,
                'study_program_id' => $data['study_program_id'],
                'entry_year' => $data['entry_year'] ?? null,
                'gpa' => $data['gpa'] ?? null,
            ]);

            if ($log) {
                ActivityLog::record('mahasiswa.tambah', "Menambah mahasiswa {$student->name} ({$student->nim})", $student);
            }

            return $student;
        });
    }
}

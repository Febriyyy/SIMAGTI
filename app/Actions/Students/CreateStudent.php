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
    public function handle(User $actor, array $data): User
    {
        return DB::transaction(function () use ($data) {
            $student = User::create([
                'name' => $data['name'],
                'email' => mb_strtolower($data['email']),
                'password' => $data['password'],
                'role' => 'mahasiswa',
                'nim' => $data['nim'],
                'is_active' => true,
            ]);

            StudentProfile::create([
                'user_id' => $student->id,
                'study_program_id' => $data['study_program_id'],
                'entry_year' => $data['entry_year'] ?? null,
                'gpa' => $data['gpa'] ?? null,
            ]);

            ActivityLog::record('mahasiswa.tambah', "Menambah mahasiswa {$student->name} ({$student->nim})", $student);

            return $student;
        });
    }
}

<?php

namespace App\Actions\Supervisors;

use App\Models\ActivityLog;
use App\Models\CompanyStaffProfile;
use App\Models\Internship;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * TU Fakultas mengaktifkan Company Supervisor dari data yang diisi mahasiswa.
 * Satu kelompok = mahasiswa di perusahaan yang sama dengan email supervisor yang sama (satu kloter),
 * sehingga satu supervisor cukup diaktifkan sekali untuk beberapa mahasiswa.
 */
class ActivateCompanySupervisor
{
    /** Penempatan dalam satu kelompok yang menunggu supervisor; hak akses dicek per penempatan. */
    public function group(User $actor, int $companyId, string $email): Collection
    {
        $internships = Internship::awaitingSupervisor()
            ->with('student')
            ->where('company_id', $companyId)
            ->where('supervisor_email', mb_strtolower(trim($email)))
            ->get();

        if ($internships->isEmpty()) {
            throw ValidationException::withMessages(['aturan' => 'Tidak ada mahasiswa yang menunggu supervisor dengan email ini.']);
        }

        foreach ($internships as $internship) {
            Gate::forUser($actor)->authorize('activateSupervisor', $internship);
        }

        return $internships;
    }

    /** Email belum terdaftar: terbitkan undangan (link aktivasi 14 hari). Undangan lama untuk email ini dibatalkan. */
    public function invite(User $actor, int $companyId, string $email): Invitation
    {
        $email = mb_strtolower(trim($email));
        $internships = $this->group($actor, $companyId, $email);

        if (User::where('email', $email)->exists()) {
            throw ValidationException::withMessages(['aturan' => "Email {$email} sudah punya akun. Gunakan tombol Tautkan."]);
        }

        return DB::transaction(function () use ($actor, $companyId, $email, $internships) {
            Invitation::where('email', $email)
                ->where('company_id', $companyId)
                ->whereNull('accepted_at')
                ->where('expires_at', '>', now())
                ->update(['expires_at' => now()]);

            $invitation = Invitation::create([
                'email' => $email,
                'role' => 'supervisor',
                'company_id' => $companyId,
                'internship_id' => $internships->first()->id,
                'invited_by' => $actor->id,
                'expires_at' => now()->addDays(14),
            ]);

            ActivityLog::record(
                'supervisor.undang',
                "Menerbitkan undangan Company Supervisor untuk {$email} (".$internships->count().' mahasiswa)',
                $invitation
            );

            return $invitation;
        });
    }

    /** Email sudah punya akun Company Supervisor: langsung ditautkan ke mahasiswa kelompok ini, tanpa akun kedua. */
    public function link(User $actor, int $companyId, string $email): User
    {
        $email = mb_strtolower(trim($email));
        $internships = $this->group($actor, $companyId, $email);
        $user = User::where('email', $email)->first();

        if (! $user) {
            throw ValidationException::withMessages(['aturan' => "Email {$email} belum punya akun. Terbitkan undangan."]);
        }

        if ($user->role !== 'supervisor') {
            throw ValidationException::withMessages(['aturan' => "Email {$email} sudah dipakai akun dengan peran {$user->roleLabel()}."]);
        }

        DB::transaction(function () use ($user, $companyId, $internships) {
            CompanyStaffProfile::firstOrCreate(
                ['user_id' => $user->id],
                ['company_id' => $companyId, 'division_id' => $internships->first()->division_id, 'job_title' => $internships->first()->supervisor_title]
            );

            foreach ($internships as $internship) {
                $internship->assignSupervisor($user);
            }

            ActivityLog::record(
                'supervisor.tautkan',
                "Menautkan {$user->name} sebagai Company Supervisor untuk ".$internships->map(fn ($i) => $i->student->name)->implode(', '),
                $internships->first()
            );
        });

        return $user;
    }

    /** Koreksi email supervisor yang salah ketik, sebelum undangan dikirim. */
    public function changeEmail(User $actor, int $companyId, string $from, string $to): int
    {
        $internships = $this->group($actor, $companyId, $from);
        $to = mb_strtolower(trim($to));

        DB::transaction(function () use ($internships, $from, $to) {
            foreach ($internships as $internship) {
                $internship->update(['supervisor_email' => $to]);
            }

            ActivityLog::record('supervisor.email', "Mengoreksi email Company Supervisor {$from} menjadi {$to}", $internships->first());
        });

        return $internships->count();
    }
}

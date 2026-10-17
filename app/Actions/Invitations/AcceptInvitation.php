<?php

namespace App\Actions\Invitations;

use App\Models\ActivityLog;
use App\Models\CompanyStaffProfile;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Mentor / ketua divisi mengaktifkan akun lewat link undangan. */
class AcceptInvitation
{
    public function handle(Invitation $invitation, string $name, string $password): User
    {
        return DB::transaction(function () use ($invitation, $name, $password) {
            $invitation = Invitation::lockForUpdate()->findOrFail($invitation->id);

            if (! $invitation->isUsable()) {
                throw ValidationException::withMessages(['aturan' => 'Undangan ini sudah dipakai atau kedaluwarsa.']);
            }

            if (User::where('email', $invitation->email)->exists()) {
                throw ValidationException::withMessages(['aturan' => 'Email undangan ini sudah terdaftar. Hubungi kampus untuk menautkan akun Anda.']);
            }

            $user = User::create([
                'name' => $name,
                'email' => $invitation->email,
                'password' => $password,
                'role' => $invitation->role,
                'is_active' => true,
            ]);
            $user->forceFill(['email_verified_at' => now()])->save();

            CompanyStaffProfile::create([
                'user_id' => $user->id,
                'company_id' => $invitation->company_id,
                'division_id' => $invitation->internship?->division_id,
            ]);

            $invitation->internship?->assignStaff($user, $invitation->role);

            $invitation->update(['accepted_at' => now(), 'user_id' => $user->id]);

            ActivityLog::record('undangan.terima', "{$name} mengaktifkan akun {$invitation->role} lewat undangan", $invitation);

            return $user;
        });
    }
}

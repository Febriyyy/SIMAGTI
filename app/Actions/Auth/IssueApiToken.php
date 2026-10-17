<?php

namespace App\Actions\Auth;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/** Menukar email + kata sandi dengan token API (Bearer). */
class IssueApiToken
{
    /** @return array{token: string, user: User} */
    public function handle(string $email, string $password, string $deviceName): array
    {
        $user = User::where('email', mb_strtolower(trim($email)))->first();

        // Pesan sama untuk semua kegagalan agar tidak membocorkan email mana yang terdaftar.
        if (! $user || ! $user->is_active || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau kata sandi tidak cocok, atau akun belum aktif.'],
            ]);
        }

        $token = $user->createToken($deviceName)->plainTextToken;

        ActivityLog::record('api.token', "Membuat token API untuk perangkat \"{$deviceName}\"", null, null, $user);

        return ['token' => $token, 'user' => $user];
    }
}

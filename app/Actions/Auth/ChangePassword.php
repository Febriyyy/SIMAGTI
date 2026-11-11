<?php

namespace App\Actions\Auth;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Pengguna mengganti kata sandinya sendiri. Sesi di perangkat lain dan semua token API ikut berakhir;
 * sesi yang sedang dipakai diregenerasi oleh controller.
 */
class ChangePassword
{
    public function handle(User $user, string $password, ?string $keepSessionId = null): void
    {
        DB::transaction(function () use ($user, $password, $keepSessionId) {
            $user->forceFill(['password' => $password, 'must_change_password' => false])->save();
            Sessions::endFor($user, $keepSessionId);

            ActivityLog::record('akun.sandi', 'Mengganti kata sandi', $user, null, $user);
        });
    }
}

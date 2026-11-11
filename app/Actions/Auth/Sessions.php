<?php

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Mengakhiri sesi login dan token API milik satu akun. */
class Sessions
{
    public static function endFor(User $user, ?string $keepSessionId = null): void
    {
        $user->tokens()->delete();

        // Cookie "Ingat saya" di perangkat lain tidak berlaku lagi.
        $user->forceFill(['remember_token' => Str::random(60)])->save();

        if (config('session.driver') === 'database') {
            DB::connection(config('session.connection'))
                ->table(config('session.table', 'sessions'))
                ->where('user_id', $user->id)
                ->when($keepSessionId, fn ($q) => $q->where('id', '!=', $keepSessionId))
                ->delete();
        }
    }
}

<?php

namespace App\Actions\Auth;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Admin mengatur ulang kata sandi akun lain: dibuatkan kata sandi sementara yang tampil sekali,
 * semua sesi dan token akun itu berakhir, dan pemiliknya wajib mengganti kata sandi saat masuk.
 */
class ResetPassword
{
    /** @return string kata sandi sementara (tidak disimpan dalam bentuk asli) */
    public function handle(User $admin, User $account): string
    {
        if ($account->is($admin)) {
            throw ValidationException::withMessages(['password' => 'Gunakan menu Ganti kata sandi untuk akun Anda sendiri.']);
        }

        $temporary = Str::password(10, symbols: false);

        DB::transaction(function () use ($account, $temporary) {
            $account->forceFill(['password' => $temporary, 'must_change_password' => true])->save();
            Sessions::endFor($account);

            ActivityLog::record('akun.sandi.reset', "Mengatur ulang kata sandi {$account->name}", $account);
        });

        return $temporary;
    }
}

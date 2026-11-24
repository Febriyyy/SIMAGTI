<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

/**
 * Lupa kata sandi: tautan atur ulang dikirim ke email (berlaku 60 menit, sekali pakai).
 * Pesan selalu sama apa pun emailnya, supaya tidak bisa dipakai menebak email yang terdaftar.
 * Akun nonaktif tidak dikirimi tautan.
 */
class PasswordResetController extends Controller
{
    public const SENT = 'Jika email terdaftar, tautan atur ulang kata sandi sudah dikirim. Periksa juga folder spam.';

    public function request(): View
    {
        return view('auth.lupa-sandi');
    }

    public function email(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email', 'max:150']], [
            'email.required' => 'Isi email akun Anda.',
            'email.email' => 'Format email tidak valid.',
        ]);

        $user = User::where('email', $data['email'])->where('is_active', true)->first();
        if ($user) {
            try {
                Password::sendResetLink(['email' => $user->email]);
                ActivityLog::record('akun.lupa_sandi', 'Meminta tautan atur ulang kata sandi', null, null, $user);
            } catch (\Throwable $e) {
                report($e); // gangguan SMTP: dicatat, pengguna tetap melihat pesan yang sama
            }
        }

        return back()->with('status', self::SENT);
    }

    public function edit(Request $request, string $token): View
    {
        return view('auth.atur-sandi', ['token' => $token, 'email' => (string) $request->query('email')]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ], [
            'password.required' => 'Isi kata sandi baru.',
            'password.confirmed' => 'Kedua kata sandi belum sama.',
            'password.min' => 'Kata sandi minimal 8 karakter.',
        ]);

        $status = Password::reset($data, function (User $user, string $password) {
            $user->forceFill([
                'password' => $password,
                'must_change_password' => false,
                'remember_token' => Str::random(60),
            ])->save();
            event(new PasswordReset($user));
            ActivityLog::record('akun.atur_sandi', 'Mengatur ulang kata sandi lewat email', null, null, $user);
        });

        if ($status !== Password::PASSWORD_RESET) {
            return back()->withInput($request->only('email'))
                ->withErrors(['email' => 'Tautan tidak berlaku atau sudah kedaluwarsa. Minta tautan baru.']);
        }

        return redirect()->route('login')->with('status', 'Kata sandi diperbarui. Silakan masuk dengan kata sandi baru.');
    }
}

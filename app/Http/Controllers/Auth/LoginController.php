<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function show(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // Akun yang dinonaktifkan tidak boleh masuk.
        $attempt = Auth::attempt(
            ['email' => $credentials['email'], 'password' => $credentials['password'], 'is_active' => true],
            $request->boolean('remember')
        );

        if (! $attempt) {
            return back()
                ->withErrors(['email' => 'Email atau kata sandi tidak cocok, atau akun belum aktif.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();
        $request->user()->forceFill(['last_login_at' => now()])->save();
        ActivityLog::record('login', 'Masuk ke sistem');

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        ActivityLog::record('logout', 'Keluar dari sistem');

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/** Token milik akun yang sudah dinonaktifkan tidak boleh dipakai lagi. */
class EnsureActiveAccount
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->is_active) {
            $user->tokens()->delete();

            // Web: keluarkan dari sesi lalu kembali ke halaman masuk (akun dinonaktifkan admin)
            if (! $request->expectsJson() && ! $request->is('api/*')) {
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->withErrors(['email' => 'Akun Anda sudah dinonaktifkan. Hubungi admin.']);
            }

            abort(403, 'Akun ini sudah dinonaktifkan.');
        }

        return $next($request);
    }
}

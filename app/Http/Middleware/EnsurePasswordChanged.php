<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Selama kata sandi sementara belum diganti, semua halaman diarahkan ke halaman Ganti kata sandi. */
class EnsurePasswordChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->must_change_password && ! $request->routeIs('akun.sandi', 'akun.sandi.update', 'logout')) {
            if ($request->expectsJson() || $request->is('api/*')) {
                abort(403, 'Ganti kata sandi sementara lewat aplikasi web terlebih dahulu.');
            }

            return redirect()->route('akun.sandi');
        }

        return $next($request);
    }
}

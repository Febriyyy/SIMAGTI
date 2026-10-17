<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Token milik akun yang sudah dinonaktifkan tidak boleh dipakai lagi. */
class EnsureActiveAccount
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->is_active) {
            $user->tokens()->delete();
            abort(403, 'Akun ini sudah dinonaktifkan.');
        }

        return $next($request);
    }
}

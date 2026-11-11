<?php

namespace App\Http\Controllers;

use App\Actions\Auth\ChangePassword;
use App\Http\Requests\ChangePasswordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Semua peran: ganti kata sandi sendiri. */
class PasswordController extends Controller
{
    public function edit(Request $request): View
    {
        return view('akun.sandi', ['forced' => (bool) $request->user()->must_change_password]);
    }

    public function update(ChangePasswordRequest $request, ChangePassword $action): RedirectResponse
    {
        $request->session()->regenerate();
        $action->handle($request->user(), $request->validated('password'), $request->session()->getId());

        return redirect()->route('dashboard')->with('status', 'Kata sandi berhasil diganti. Sesi di perangkat lain sudah diakhiri.');
    }
}

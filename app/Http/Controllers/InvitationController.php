<?php

namespace App\Http\Controllers;

use App\Actions\Invitations\AcceptInvitation;
use App\Http\Requests\Invitations\AcceptInvitationRequest;
use App\Models\Invitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/** Halaman publik untuk mengaktifkan akun mentor / ketua divisi lewat link undangan. */
class InvitationController extends Controller
{
    public function show(string $token): View
    {
        $invitation = Invitation::with(['company', 'internship.student'])->where('token', $token)->firstOrFail();

        return view('undangan.show', compact('invitation'));
    }

    public function store(AcceptInvitationRequest $request, string $token, AcceptInvitation $accept): RedirectResponse
    {
        $invitation = Invitation::where('token', $token)->firstOrFail();

        $data = $request->validated();
        $accept->handle($invitation, $data['name'], $data['password']);

        return redirect()->route('login')->with('status', 'Akun aktif. Silakan masuk dengan email undangan dan kata sandi baru.');
    }
}

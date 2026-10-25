<?php

namespace App\Http\Controllers;

use App\Actions\Supervisors\ConfirmPlacement;
use App\Models\Internship;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Konfirmasi penempatan oleh Company Supervisor, dan tindak lanjut sanggahan oleh TU Fakultas. */
class SupervisorConfirmationController extends Controller
{
    public function update(Request $request, Internship $internship, ConfirmPlacement $confirm): RedirectResponse
    {
        abort_unless($internship->isSupervisedBy($request->user()) && $internship->status === 'berjalan', 403);

        $data = $request->validate([
            'keputusan' => ['required', Rule::in(['benar', 'salah'])],
            'catatan' => ['nullable', 'string', 'max:500'],
        ]);

        $confirm->handle($request->user(), $internship, $data['keputusan'] === 'benar', $data['catatan'] ?? null);

        return back()->with('status', $data['keputusan'] === 'benar'
            ? "Penempatan {$internship->student->name} dikonfirmasi. Terima kasih."
            : 'Catatan dikirim ke TU Fakultas untuk diperbaiki.');
    }

    public function askAgain(Request $request, Internship $internship, ConfirmPlacement $confirm): RedirectResponse
    {
        abort_unless($internship->supervisor_confirmation === 'disputed', 404);
        $confirm->askAgain($request->user(), $internship);

        return back()->with('status', 'Supervisor diminta mengonfirmasi ulang.');
    }

    public function release(Request $request, Internship $internship, ConfirmPlacement $confirm): RedirectResponse
    {
        abort_unless($internship->supervisor_confirmation === 'disputed', 404);
        $confirm->release($request->user(), $internship);

        return back()->with('status', 'Supervisor dilepas. Perbaiki email lalu aktifkan ulang dari antrean.');
    }
}

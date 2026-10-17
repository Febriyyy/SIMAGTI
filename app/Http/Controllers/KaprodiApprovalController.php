<?php

namespace App\Http\Controllers;

use App\Actions\Applications\ReviewKaprodiApproval;
use App\Http\Requests\Applications\ReviewKaprodiRequest;
use App\Models\Internship;
use App\Models\InternshipApproval;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/** Kaprodi memberi persetujuan akhir KP mandiri dan menunjuk dosen pembimbing. */
class KaprodiApprovalController extends Controller
{
    public function index(): View
    {
        $waiting = Internship::with(['student', 'company', 'division', 'approvals.reviewer', 'invitations'])
            ->where('track', 'mandiri')
            ->where('status', 'diajukan')
            ->whereHas('approvals', fn ($q) => $q
                ->where('step', InternshipApproval::STEP_BIRO)
                ->where('status', 'approved'))
            ->whereHas('approvals', fn ($q) => $q
                ->where('step', InternshipApproval::STEP_KAPRODI)
                ->where('status', 'pending'))
            ->oldest()
            ->get();

        $lecturers = User::where('role', 'dospem')->where('is_active', true)->orderBy('name')->get();

        return view('pengajuan.kaprodi', compact('waiting', 'lecturers'));
    }

    public function update(ReviewKaprodiRequest $request, Internship $internship, ReviewKaprodiApproval $review): RedirectResponse
    {
        $data = $request->validated();

        $review->handle(
            $request->user(),
            $internship,
            $request->approved(),
            isset($data['lecturer_id']) ? (int) $data['lecturer_id'] : null,
            $data['note'] ?? null
        );

        return redirect()->route('kaprodi.persetujuan')->with(
            'status',
            $request->approved() ? 'KP disetujui dan penempatan aktif.' : 'Pengajuan ditolak.'
        );
    }
}

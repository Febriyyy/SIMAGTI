<?php

namespace App\Http\Controllers;

use App\Actions\Applications\ReviewBiroVerification;
use App\Http\Requests\Applications\ReviewBiroRequest;
use App\Models\Internship;
use App\Models\InternshipApproval;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/** Biro kerjasama memverifikasi perusahaan dari jalur KP mandiri. */
class BiroVerificationController extends Controller
{
    public function index(): View
    {
        $waiting = Internship::with(['student', 'company', 'division'])
            ->where('track', 'mandiri')
            ->where('status', 'diajukan')
            ->whereHas('approvals', fn ($q) => $q
                ->where('step', InternshipApproval::STEP_BIRO)
                ->where('status', 'pending'))
            ->oldest()
            ->get();

        return view('pengajuan.biro', compact('waiting'));
    }

    public function update(ReviewBiroRequest $request, Internship $internship, ReviewBiroVerification $review): RedirectResponse
    {
        $review->handle(
            $request->user(),
            $internship,
            $request->approved(),
            $request->validated('note')
        );

        return redirect()->route('biro.verifikasi')->with(
            'status',
            $request->approved() ? 'Perusahaan terverifikasi. Pengajuan diteruskan ke Kaprodi.' : 'Pengajuan ditolak.'
        );
    }
}

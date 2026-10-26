<?php

namespace App\Http\Controllers;

use App\Actions\Applications\ReviewVerification;
use App\Http\Requests\Applications\ReviewVerificationRequest;
use App\Models\Internship;
use App\Models\InternshipApproval;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/** TU Fakultas memverifikasi perusahaan dari jalur KP mandiri. */
class VerificationController extends Controller
{
    public function index(): View
    {
        $waiting = Internship::with(['student', 'company', 'division'])
            ->where('track', 'mandiri')
            ->where('status', 'diajukan')
            ->whereHas('approvals', fn ($q) => $q
                ->where('step', InternshipApproval::STEP_VERIFY)
                ->where('status', 'pending'))
            ->oldest()
            ->get();

        return view('pengajuan.verifikasi', compact('waiting'));
    }

    public function update(ReviewVerificationRequest $request, Internship $internship, ReviewVerification $review): RedirectResponse
    {
        $review->handle(
            $request->user(),
            $internship,
            $request->approved(),
            $request->validated('note')
        );

        return redirect()->route('tu.verifikasi')->with(
            'status',
            $request->approved() ? 'Perusahaan terverifikasi. Pengajuan diteruskan ke Kaprodi.' : 'Pengajuan ditolak.'
        );
    }
}

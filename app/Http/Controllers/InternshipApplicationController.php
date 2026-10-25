<?php

namespace App\Http\Controllers;

use App\Actions\Applications\SubmitInternshipApplication;
use App\Http\Requests\Applications\SubmitInternshipRequest;
use App\Models\Internship;
use App\Support\KpProgress;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Halaman "Status KP" mahasiswa: pengajuan (dua jalur) dan kemajuannya langkah demi langkah. */
class InternshipApplicationController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user()->load('studentProfile.studyProgram');

        $internship = Internship::with(['company', 'division', 'lecturer', 'supervisor', 'approvals.reviewer', 'invitations', 'staff', 'creditRecognition'])
            ->where('student_id', $user->id)
            ->latest('id')
            ->first();

        // Formulir tampil jika belum ada pengajuan, atau pengajuan terakhir ditolak, dibatalkan, atau pindah tempat.
        $canSubmit = ! $internship || in_array($internship->status, ['ditolak', 'dibatalkan', 'pindah'], true);
        $steps = $internship ? KpProgress::steps($internship) : [];
        $semester = $user->studentProfile?->semester();

        return view('pengajuan.index', compact('internship', 'canSubmit', 'steps', 'semester', 'user'));
    }

    public function store(SubmitInternshipRequest $request, SubmitInternshipApplication $submit): RedirectResponse
    {
        $internship = $submit->handle($request->user(), $request->validated());

        return redirect()->route('kp.status')->with('status', $internship->isSeminar()
            ? 'Pengajuan KP terkirim. Menunggu persetujuan Kaprodi.'
            : 'Pengajuan KP terkirim. Menunggu verifikasi Biro Kerjasama.');
    }
}

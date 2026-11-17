<?php

namespace App\Http\Controllers;

use App\Actions\Applications\SubmitInternshipApplication;
use App\Http\Requests\Applications\SubmitInternshipRequest;
use App\Models\Internship;
use App\Models\JobPosting;
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

        $internship = Internship::with(['company', 'division', 'lecturer', 'supervisor', 'approvals.reviewer', 'invitations', 'staff', 'documents', 'assessment', 'defenseScore', 'defenseSchedule.examiner'])
            ->where('student_id', $user->id)
            ->latest('id')
            ->first();

        // Formulir tampil jika belum ada pengajuan, atau pengajuan terakhir ditolak, dibatalkan, atau pindah tempat.
        $canSubmit = ! $internship || in_array($internship->status, ['ditolak', 'dibatalkan', 'pindah'], true);
        $steps = $internship ? KpProgress::steps($internship) : [];
        $semester = $user->studentProfile?->semester();

        // Dari halaman Lowongan: data perusahaan dan posisi terisi otomatis
        $posting = $canSubmit && $request->filled('lowongan')
            ? JobPosting::with('company')->where('status', 'published')->find($request->integer('lowongan'))
            : null;
        $prefill = $posting ? [
            'company_name' => $posting->company->name,
            'industry' => $posting->company->industry,
            'position' => $posting->title,
            'city' => $posting->company->city,
            'company_email' => $posting->company->email,
            'address' => $posting->company->address,
            'phone' => $posting->company->phone,
            'website' => $posting->company->website,
        ] : [];

        return view('pengajuan.index', compact('internship', 'canSubmit', 'steps', 'semester', 'user', 'posting', 'prefill'));
    }

    public function store(SubmitInternshipRequest $request, SubmitInternshipApplication $submit): RedirectResponse
    {
        $internship = $submit->handle($request->user(), $request->validated());

        return redirect()->route('kp.status')->with('status', 'Pengajuan KP terkirim. Menunggu verifikasi TU Fakultas.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Actions\Applications\SubmitIndependentInternship;
use App\Http\Requests\Applications\SubmitInternshipRequest;
use App\Models\Internship;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Halaman "Pengajuan KP" milik mahasiswa (jalur KP mandiri). */
class InternshipApplicationController extends Controller
{
    public function index(Request $request): View
    {
        $internship = Internship::with(['company', 'approvals.reviewer', 'invitations'])
            ->where('student_id', $request->user()->id)
            ->latest('id')
            ->first();

        // Formulir tampil jika belum ada pengajuan, atau pengajuan terakhir sudah ditolak.
        $canSubmit = ! $internship || $internship->status === 'ditolak';

        return view('pengajuan.index', compact('internship', 'canSubmit'));
    }

    public function store(SubmitInternshipRequest $request, SubmitIndependentInternship $submit): RedirectResponse
    {
        $submit->handle($request->user(), $request->validated());

        return redirect()->route('pengajuan.index')->with('status', 'Pengajuan KP terkirim. Menunggu verifikasi Biro Kerjasama.');
    }
}

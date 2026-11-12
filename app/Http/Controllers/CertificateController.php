<?php

namespace App\Http\Controllers;

use App\Actions\Results\IssueCertificate;
use App\Models\Certificate;
use App\Models\Internship;
use App\Support\AssessmentStandard;
use App\Support\Campus;
use App\Support\Qr;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/** Sertifikat KP: halaman cetak (perlu login) dan verifikasi publik lewat QR (tanpa login, tanpa NIM). */
class CertificateController extends Controller
{
    public function show(Request $request, Internship $internship, IssueCertificate $issue): View
    {
        Gate::authorize('view', $internship);
        abort_unless($internship->result === 'diterima', 404, 'Sertifikat hanya tersedia untuk KP yang diterima.');

        // KP yang diputuskan sebelum fitur sertifikat ada: terbitkan saat pertama kali dibuka.
        $certificate = $issue->handle($internship->loadMissing('student', 'company', 'lecturer'), $request->user());
        abort_unless($certificate?->isValid(), 404, 'Sertifikat ini tidak berlaku.');

        $verifyUrl = route('sertifikat.verifikasi', $certificate->verification_token);

        return view('sertifikat.cetak', [
            'certificate' => $certificate->load('template'),
            'internship' => $internship,
            'verifyUrl' => $verifyUrl,
            'qr' => Qr::svg($verifyUrl),
            'campus' => [
                'university' => Campus::university(),
                'faculty' => Campus::faculty(),
                'program' => (string) Campus::get('campus.program'),
                'city' => Campus::city(),
            ],
        ]);
    }

    public function verify(string $token): Response
    {
        $certificate = Certificate::with('internship.student', 'internship.company', 'internship.division', 'template')
            ->where('verification_token', $token)
            ->first();

        // Keterangan nilai hanya untuk sertifikat yang sah dan penilaian yang sudah divalidasi dosen pembimbing.
        $grade = null;
        $assessment = $certificate?->isValid() ? $certificate->internship->assessment()->with('scores', 'rubric.aspects', 'validator')->first() : null;
        if ($assessment?->isValidated()) {
            $grade = [
                'assessment' => $assessment,
                'letter' => $certificate->internship->final_grade ?? $assessment->letter_grade,
                'score' => (float) ($certificate->internship->final_score ?? $assessment->final_score),
                'breakdown' => AssessmentStandard::breakdown($assessment->rubric, $assessment->scores, AssessmentStandard::weightsFor($assessment)),
            ];
        }

        return response()
            ->view('sertifikat.verifikasi', [
                'certificate' => $certificate,
                'grade' => $grade,
                'university' => Campus::university(),
                'faculty' => Campus::faculty(),
                'program' => (string) Campus::get('campus.program'),
                'city' => Campus::city(),
            ], $certificate ? 200 : 404)
            ->header('X-Robots-Tag', 'noindex');
    }
}

<?php

namespace App\Http\Controllers;

use App\Actions\Results\IssueCertificate;
use App\Models\Certificate;
use App\Models\CertificateTemplate;
use App\Models\Internship;
use App\Support\AssessmentStandard;
use App\Support\Campus;
use App\Support\Logos;
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
        $certificate = $issue->handle($internship->loadMissing('student', 'company', 'division', 'lecturer'), $request->user());
        abort_unless($certificate?->isValid(), 404, 'Sertifikat ini tidak berlaku.');

        $verifyUrl = route('sertifikat.verifikasi', $certificate->verification_token);

        // Template kaprodi mengikuti prodi mahasiswa (milik prodi itu, atau template umum)
        $template = CertificateTemplate::forProgram($internship->programId());
        $certificate->setRelation('template', $template);

        return view('sertifikat.cetak', [
            'certificate' => $certificate,
            'internship' => $internship,
            'verifyUrl' => $verifyUrl,
            'qr' => Qr::svg($verifyUrl),
            'campusLogo' => Logos::campus(),
            'facultyLogo' => Logos::faculty(),
            'kaprodiSignature' => Logos::dataUri($template->signature),
            'divisionSignature' => Logos::dataUri($internship->division?->headSignature),
            'campus' => [
                'university' => Campus::university(),
                'faculty' => Campus::faculty(),
                'program' => Campus::program($internship->programId()),
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
        $assessment = $certificate?->isValid() ? $certificate->internship->assessment()->with('scores', 'rubric.aspects', 'validator', 'assessor')->first() : null;
        if ($assessment?->isValidated()) {
            $scores = $assessment->scores->keyBy('rubric_aspect_id');
            $aspects = $assessment->rubric->aspects->where('kind', '!=', 'text')->groupBy('category')
                ->map(fn ($items) => $items->map(fn ($a) => [
                    'name' => $a->name,
                    'score' => $scores->get($a->id)?->score !== null ? (float) $scores->get($a->id)->score : null,
                    'label' => $scores->get($a->id)?->score !== null ? AssessmentStandard::scaleLabel((float) $scores->get($a->id)->score) : null,
                ])->values());
            $grade = [
                'assessment' => $assessment,
                'aspects' => $aspects,
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
                'program' => Campus::program($certificate?->internship?->programId()),
                'city' => Campus::city(),
            ], $certificate ? 200 : 404)
            ->header('X-Robots-Tag', 'noindex');
    }
}

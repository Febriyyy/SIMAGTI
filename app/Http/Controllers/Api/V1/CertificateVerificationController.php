<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use Illuminate\Http\JsonResponse;

/** Verifikasi publik sertifikat lewat token pada QR. Tidak memerlukan login dan tidak membuka NIM. */
class CertificateVerificationController extends Controller
{
    public function __invoke(string $token): JsonResponse
    {
        $certificate = Certificate::with('internship.student', 'internship.company')
            ->where('verification_token', $token)
            ->first();

        if (! $certificate) {
            return response()->json(['valid' => false, 'message' => 'Sertifikat tidak ditemukan.'], 404);
        }

        $internship = $certificate->internship;

        return response()->json([
            'valid' => $certificate->isValid(),
            'status' => $certificate->status,
            'number' => $certificate->number,
            'issued_at' => $certificate->issued_at?->toDateString(),
            'student_name' => $internship->student->name,
            'company_name' => $internship->company->name,
            'start_date' => $internship->start_date->toDateString(),
            'end_date' => $internship->end_date->toDateString(),
            'revoked_reason' => $certificate->status === 'dicabut' ? $certificate->revoked_reason : null,
        ]);
    }
}

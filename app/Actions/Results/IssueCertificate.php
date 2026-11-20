<?php

namespace App\Actions\Results;

use App\Models\ActivityLog;
use App\Models\Certificate;
use App\Models\CertificateTemplate;
use App\Models\Internship;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Menerbitkan sertifikat untuk KP yang hasilnya Diterima. Aman dipanggil berulang:
 * bila sertifikat sudah ada, yang lama dipakai.
 */
class IssueCertificate
{
    public function handle(Internship $internship, ?User $issuer = null): ?Certificate
    {
        if ($internship->result !== 'diterima') {
            return null;
        }

        if ($existing = $internship->certificate()->first()) {
            return $existing;
        }

        return DB::transaction(function () use ($internship, $issuer) {
            $template = CertificateTemplate::campus();

            $certificate = Certificate::create([
                'internship_id' => $internship->id,
                'template_id' => $template->id,
                'number' => Certificate::nextNumber(),
                'status' => 'terbit',
                'issued_by' => $issuer?->id ?? $internship->result_by,
                'issued_at' => now(),
            ]);

            ActivityLog::record('sertifikat.terbit', "Menerbitkan sertifikat KP {$internship->student->name} ({$certificate->number})", $internship, null, $issuer);

            return $certificate;
        });
    }
}

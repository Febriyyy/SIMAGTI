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
            $kaprodi = User::where('role', 'kaprodi')->where('is_active', true)->orderBy('id')->first();
            $template = CertificateTemplate::firstOrCreate(
                ['scope' => 'kampus', 'name' => 'Sertifikat KP kampus'],
                ['signer_name' => $kaprodi?->name, 'signer_title' => 'Ketua Program Studi', 'created_by' => $kaprodi?->id]
            );

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

<?php

namespace App\Actions\Applications;

use App\Models\ActivityLog;
use App\Models\Internship;
use App\Models\InternshipApproval;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Langkah 1 KP mandiri: biro kerjasama memverifikasi perusahaan. */
class ReviewBiroVerification
{
    public function handle(User $actor, Internship $internship, bool $approve, ?string $note = null): Internship
    {
        return DB::transaction(function () use ($actor, $internship, $approve, $note) {
            $internship = Internship::with('company', 'student')->lockForUpdate()->findOrFail($internship->id);

            if ($internship->track !== 'mandiri' || $internship->status !== 'diajukan') {
                throw ValidationException::withMessages(['aturan' => 'Pengajuan ini tidak sedang menunggu verifikasi.']);
            }

            $approval = $internship->approvals()->where('step', InternshipApproval::STEP_BIRO)->firstOrFail();

            if ($approval->status !== 'pending') {
                throw ValidationException::withMessages(['aturan' => 'Pengajuan ini sudah diverifikasi sebelumnya.']);
            }

            $approval->update([
                'status' => $approve ? 'approved' : 'rejected',
                'reviewed_by' => $actor->id,
                'reviewed_at' => now(),
                'note' => $note,
            ]);

            if ($approve) {
                $internship->company->update([
                    'status' => 'mandiri_terverifikasi',
                    'verified_by' => $actor->id,
                    'verified_at' => now(),
                ]);
            } else {
                // Ditolak: penempatan tidak lagi aktif, mahasiswa boleh mengajukan ulang.
                $internship->update(['status' => 'ditolak']);
            }

            ActivityLog::record(
                $approve ? 'kp.verifikasi.setuju' : 'kp.verifikasi.tolak',
                ($approve ? 'Memverifikasi' : 'Menolak').' perusahaan '.$internship->company->name." untuk {$internship->student->name}",
                $internship
            );

            return $internship;
        });
    }
}

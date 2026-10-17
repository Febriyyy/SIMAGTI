<?php

namespace App\Actions\Applications;

use App\Models\ActivityLog;
use App\Models\Internship;
use App\Models\InternshipApproval;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Langkah 2 KP mandiri: kaprodi menyetujui dan menunjuk dosen pembimbing, atau menolak. */
class ReviewKaprodiApproval
{
    public function handle(User $actor, Internship $internship, bool $approve, ?int $lecturerId = null, ?string $note = null): Internship
    {
        return DB::transaction(function () use ($actor, $internship, $approve, $lecturerId, $note) {
            $internship = Internship::with('company', 'student')->lockForUpdate()->findOrFail($internship->id);

            if ($internship->track !== 'mandiri' || $internship->status !== 'diajukan') {
                throw ValidationException::withMessages(['aturan' => 'Pengajuan ini tidak sedang menunggu persetujuan.']);
            }

            $biro = $internship->approvals()->where('step', InternshipApproval::STEP_BIRO)->firstOrFail();

            if ($biro->status !== 'approved') {
                throw ValidationException::withMessages(['aturan' => 'Perusahaan belum diverifikasi Biro Kerjasama.']);
            }

            $approval = $internship->approvals()->where('step', InternshipApproval::STEP_KAPRODI)->firstOrFail();

            if ($approval->status !== 'pending') {
                throw ValidationException::withMessages(['aturan' => 'Pengajuan ini sudah diproses sebelumnya.']);
            }

            $approval->update([
                'status' => $approve ? 'approved' : 'rejected',
                'reviewed_by' => $actor->id,
                'reviewed_at' => now(),
                'note' => $note,
            ]);

            if ($approve) {
                $internship->update(['status' => 'berjalan', 'lecturer_id' => $lecturerId]);
            } else {
                $internship->update(['status' => 'ditolak']);
            }

            ActivityLog::record(
                $approve ? 'kp.setujui' : 'kp.tolak',
                ($approve ? 'Menyetujui' : 'Menolak')." KP mandiri {$internship->student->name} di {$internship->company->name}",
                $internship
            );

            return $internship;
        });
    }
}

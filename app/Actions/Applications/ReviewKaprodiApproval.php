<?php

namespace App\Actions\Applications;

use App\Actions\Documents\ManageDocuments;
use App\Models\ActivityLog;
use App\Models\Internship;
use App\Models\InternshipApproval;
use App\Models\User;
use App\Support\Notify;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Persetujuan kaprodi: menyetujui dan menunjuk dosen pembimbing, atau menolak.
 * Magang harus sudah diverifikasi biro; seminar (tanpa perusahaan) langsung ke kaprodi.
 */
class ReviewKaprodiApproval
{
    public function handle(User $actor, Internship $internship, bool $approve, ?int $lecturerId = null, ?string $note = null): Internship
    {
        return DB::transaction(function () use ($actor, $internship, $approve, $lecturerId, $note) {
            $internship = Internship::with('company', 'student')->lockForUpdate()->findOrFail($internship->id);

            if ($internship->track !== 'mandiri' || $internship->status !== 'diajukan') {
                throw ValidationException::withMessages(['aturan' => 'Pengajuan ini tidak sedang menunggu persetujuan.']);
            }

            $biro = $internship->approvals()->where('step', InternshipApproval::STEP_BIRO)->first();

            if (! $internship->isSeminar() && $biro?->status !== 'approved') {
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
                // KP mandiri di perusahaan: surat balasan (dan pakta integritas MBKM) boleh menyusul sampai batas waktu
                ManageDocuments::ensureFor($internship);
            } else {
                $internship->update(['status' => 'ditolak']);
            }

            Notify::send($internship->student, $approve ? 'Pengajuan KP disetujui' : 'Pengajuan KP tidak disetujui', $note, route('kp.status'), 'approval', $approve ? 'green' : 'red');
            if ($approve && $lecturerId) {
                Notify::send(User::find($lecturerId), "Anda ditunjuk sebagai dosen pembimbing {$internship->student->name}", $internship->placeName(), route('pantau.show', $internship), 'student');
            }

            ActivityLog::record(
                $approve ? 'kp.setujui' : 'kp.tolak',
                ($approve ? 'Menyetujui' : 'Menolak')." KP {$internship->programTypeLabel()} {$internship->student->name}: {$internship->placeName()}",
                $internship
            );

            return $internship;
        });
    }
}

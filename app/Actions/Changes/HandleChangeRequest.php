<?php

namespace App\Actions\Changes;

use App\Models\ActivityLog;
use App\Models\ChangeRequest;
use App\Models\Internship;
use App\Models\User;
use App\Support\Notify;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Pengajuan perubahan di tengah KP. Alur persetujuan:
 *  - perpanjangan: Company Supervisor menyatakan setuju (magang) -> kaprodi
 *  - pindah tempat / pengunduran diri: kaprodi
 *  - ganti Company Supervisor: TU Fakultas
 */
class HandleChangeRequest
{
    public function create(User $actor, Internship $internship, string $type, array $data): ChangeRequest
    {
        if ($internship->status !== 'berjalan') {
            throw ValidationException::withMessages(['type' => 'Perubahan hanya untuk KP yang sedang berjalan.']);
        }
        if ($internship->changeRequests()->where('status', 'pending')->exists()) {
            throw ValidationException::withMessages(['type' => 'Masih ada pengajuan perubahan yang sedang diproses.']);
        }
        $payload = match ($type) {
            'extend' => ['new_end_date' => Carbon::parse($data['new_end_date'])->toDateString(), 'old_end_date' => $internship->end_date->toDateString()],
            'replace_supervisor' => [
                'supervisor_name' => trim($data['supervisor_name']),
                'supervisor_title' => filled($data['supervisor_title'] ?? null) ? trim($data['supervisor_title']) : null,
                'supervisor_email' => mb_strtolower(trim($data['supervisor_email'])),
            ],
            'transfer' => ['company_name' => filled($data['company_name'] ?? null) ? trim($data['company_name']) : null],
            default => [],
        };

        if ($type === 'extend' && Carbon::parse($payload['new_end_date'])->lte($internship->end_date)) {
            throw ValidationException::withMessages(['new_end_date' => 'Tanggal selesai baru harus setelah tanggal selesai sekarang.']);
        }

        $step = match ($type) {
            'extend' => ! $internship->supervisor ? 'kaprodi' : 'supervisor',
            'replace_supervisor' => 'tu',
            default => 'kaprodi',
        };

        $change = ChangeRequest::create([
            'internship_id' => $internship->id,
            'type' => $type,
            'status' => 'pending',
            'step' => $step,
            'payload' => $payload,
            'reason' => trim($data['reason']),
            'requested_by' => $actor->id,
        ]);

        ActivityLog::record('perubahan.ajukan', "Mengajukan {$change->typeLabel()} untuk {$internship->student->name}", $change);
        $this->notifyStep($change);

        return $change;
    }

    public function decide(User $actor, ChangeRequest $change, bool $approve, ?string $note): ChangeRequest
    {
        if (! $change->isPending()) {
            throw ValidationException::withMessages(['keputusan' => 'Pengajuan ini sudah diputuskan.']);
        }
        if (! $approve && ! filled($note)) {
            throw ValidationException::withMessages(['catatan' => 'Tulis alasan penolakan.']);
        }

        return DB::transaction(function () use ($actor, $change, $approve, $note) {
            $internship = $change->internship;

            // Langkah supervisor (perpanjangan): setuju -> diteruskan ke kaprodi
            if ($change->step === 'supervisor' && $approve) {
                $change->update(['step' => 'kaprodi', 'supervisor_note' => $note, 'supervisor_acked_at' => now()]);
                ActivityLog::record('perubahan.supervisor', "Menyetujui {$change->typeLabel()} {$internship->student->name}", $change);
                $this->notifyStep($change);

                return $change;
            }

            $change->update([
                'status' => $approve ? 'approved' : 'rejected',
                'step' => null,
                'decision_note' => $note,
                'decided_by' => $actor->id,
                'decided_at' => now(),
            ]);

            if ($approve) {
                $this->apply($change, $internship);
            }

            ActivityLog::record($approve ? 'perubahan.setujui' : 'perubahan.tolak', ($approve ? 'Menyetujui ' : 'Menolak ')."{$change->typeLabel()} {$internship->student->name}", $change);
            Notify::send(Notify::parties($internship), "{$change->typeLabel()} {$internship->student->name} ".($approve ? 'disetujui' : 'ditolak'), $note ?? $change->summary(), route('perubahan.index'), 'history', $approve ? 'green' : 'red');

            return $change;
        });
    }

    private function apply(ChangeRequest $change, Internship $internship): void
    {
        $p = $change->payload ?? [];

        match ($change->type) {
            'extend' => $this->extend($internship, Carbon::parse($p['new_end_date'])),
            'transfer' => $internship->update(['status' => 'pindah']),
            'withdraw' => $internship->update(['status' => 'dibatalkan']),
            'replace_supervisor' => $this->replaceSupervisor($internship, $p),
        };
    }

    private function extend(Internship $internship, Carbon $end): void
    {
        $internship->update(['end_date' => $end->toDateString()]);
    }

    private function replaceSupervisor(Internship $internship, array $p): void
    {
        $internship->staff()->where('role', 'supervisor')->delete();
        $internship->update([
            'supervisor_name' => $p['supervisor_name'],
            'supervisor_title' => $p['supervisor_title'] ?? null,
            'supervisor_email' => $p['supervisor_email'],
            'supervisor_confirmation' => null,
            'supervisor_confirmation_note' => null,
            'supervisor_confirmed_at' => null,
        ]);
        // Penempatan kembali ke antrean aktivasi TU dengan data supervisor baru
    }

    private function notifyStep(ChangeRequest $change): void
    {
        $internship = $change->internship;
        $to = match ($change->step) {
            'supervisor' => $internship->supervisor,
            'kaprodi' => Notify::role('kaprodi'),
            'tu' => Notify::role('tu'),
            default => null,
        };
        Notify::send($to, "{$change->typeLabel()} {$internship->student->name} menunggu keputusan", $change->summary(), route('perubahan.antrean'), 'history');
    }
}

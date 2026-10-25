<?php

namespace App\Actions\Supervisors;

use App\Models\ActivityLog;
use App\Models\Internship;
use App\Models\User;
use App\Support\Notify;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Company Supervisor memeriksa data penempatan (posisi, divisi, periode) yang diisi mahasiswa:
 * "Benar, saya pembimbingnya" atau "Ada yang salah" dengan catatan untuk TU Fakultas.
 * Konfirmasi ini juga menjadi bukti digital bahwa perusahaan menerima mahasiswa,
 * sementara surat balasan resmi menyusul.
 */
class ConfirmPlacement
{
    public function handle(User $supervisor, Internship $internship, bool $confirm, ?string $note): Internship
    {
        if (! $confirm && ! filled($note)) {
            throw ValidationException::withMessages(['catatan' => 'Tulis bagian yang salah agar TU Fakultas bisa memperbaikinya.']);
        }

        return DB::transaction(function () use ($supervisor, $internship, $confirm, $note) {
            $internship->update([
                'supervisor_confirmation' => $confirm ? 'confirmed' : 'disputed',
                'supervisor_confirmation_note' => filled($note) ? trim($note) : null,
                'supervisor_confirmed_at' => now(),
            ]);

            $student = $internship->student;
            ActivityLog::record(
                $confirm ? 'supervisor.konfirmasi' : 'supervisor.sanggah',
                ($confirm ? 'Mengonfirmasi' : 'Menyanggah')." penempatan {$student->name}",
                $internship
            );

            if ($confirm) {
                Notify::send($student, "{$supervisor->name} mengonfirmasi penempatan Anda", null, route('kp.status'), 'verify', 'green');
            } else {
                Notify::send(Notify::role('tu'), "Penempatan {$student->name} disanggah supervisor", $note, route('tu.aktivasi').'#disanggah', 'alert', 'red');
                Notify::send($student, 'Supervisor menemukan data yang perlu diperbaiki', $note, route('kp.status'), 'alert', 'red');
            }

            return $internship;
        });
    }

    /** TU: data sudah diperbaiki, minta supervisor mengonfirmasi ulang. */
    public function askAgain(User $actor, Internship $internship): void
    {
        $internship->update(['supervisor_confirmation' => null, 'supervisor_confirmation_note' => null, 'supervisor_confirmed_at' => null]);
        ActivityLog::record('supervisor.minta_ulang', "Meminta konfirmasi ulang penempatan {$internship->student->name}", $internship);
        Notify::send($internship->supervisor, 'Mohon konfirmasi ulang penempatan '.$internship->student->name, 'Data sudah diperbaiki TU Fakultas.', route('dashboard').'#konfirmasi', 'verify');
    }

    /** TU: supervisor yang tertaut ternyata salah orang. Penempatan kembali ke antrean aktivasi. */
    public function release(User $actor, Internship $internship): void
    {
        DB::transaction(function () use ($internship) {
            $supervisor = $internship->supervisor;
            $internship->staff()->where('role', 'supervisor')->delete();
            $internship->update(['supervisor_confirmation' => null, 'supervisor_confirmation_note' => null, 'supervisor_confirmed_at' => null]);
            ActivityLog::record('supervisor.lepas', 'Melepas '.($supervisor?->name ?? 'supervisor')." dari penempatan {$internship->student->name}", $internship);
        });
    }
}

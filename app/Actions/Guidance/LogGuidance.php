<?php

namespace App\Actions\Guidance;

use App\Models\ActivityLog;
use App\Models\GuidanceSession;
use App\Models\Internship;
use App\Models\User;
use App\Support\Campus;
use App\Support\Notify;
use Illuminate\Validation\ValidationException;

/** Log bimbingan: mahasiswa mencatat, dosen pembimbing mengonfirmasi atau menolak dengan catatan. */
class LogGuidance
{
    public function create(User $student, Internship $internship, array $data): GuidanceSession
    {
        $session = GuidanceSession::create([
            'internship_id' => $internship->id,
            'held_on' => $data['held_on'],
            'mode' => $data['mode'],
            'topic' => trim($data['topic']),
            'notes' => filled($data['notes'] ?? null) ? trim($data['notes']) : null,
            'status' => 'pending',
            'created_by' => $student->id,
        ]);

        ActivityLog::record('bimbingan.catat', "Mencatat bimbingan: {$session->topic}", $session);
        Notify::send($internship->lecturer, "Bimbingan {$student->name} menunggu konfirmasi", $session->topic, route('bimbingan.antrean'), 'users');

        return $session;
    }

    public function decide(User $lecturer, GuidanceSession $session, bool $confirm, ?string $note): GuidanceSession
    {
        if ($session->status !== 'pending') {
            throw ValidationException::withMessages(['keputusan' => 'Bimbingan ini sudah diputuskan.']);
        }
        if (! $confirm && ! filled($note)) {
            throw ValidationException::withMessages(['catatan' => 'Tulis alasan penolakan.']);
        }

        $session->update([
            'status' => $confirm ? 'confirmed' : 'rejected',
            'lecturer_note' => filled($note) ? trim($note) : null,
            'decided_by' => $lecturer->id,
            'decided_at' => now(),
        ]);

        $internship = $session->internship;
        $count = $internship->confirmedGuidanceCount();
        $min = (int) Campus::rule('min_guidance');
        ActivityLog::record($confirm ? 'bimbingan.konfirmasi' : 'bimbingan.tolak', ($confirm ? 'Mengonfirmasi' : 'Menolak')." bimbingan {$internship->student->name}", $session);
        Notify::send($internship->student, $confirm ? "Bimbingan dikonfirmasi ({$count} dari {$min})" : 'Catatan bimbingan ditolak', $note, route('bimbingan.index'), 'users', $confirm ? 'green' : 'red');

        return $session;
    }
}

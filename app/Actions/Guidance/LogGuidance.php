<?php

namespace App\Actions\Guidance;

use App\Models\ActivityLog;
use App\Models\GuidanceSession;
use App\Models\Internship;
use App\Models\User;
use App\Support\Campus;
use App\Support\Notify;
use Illuminate\Validation\ValidationException;

/**
 * Log bimbingan: mahasiswa mencatat, dosen pembimbing mengonfirmasi atau menolak dengan catatan.
 * Dosen juga bisa menjadwalkan bimbingan (jam dan tautan Zoom/Meet); setelah berlangsung ditandai selesai.
 */
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

    /** @param  array{internship_id: int, held_on: string, start_time: string, mode: string, topic: string, meeting_link?: ?string, notes?: ?string}  $data */
    public function schedule(User $lecturer, Internship $internship, array $data): GuidanceSession
    {
        abort_unless((int) $internship->lecturer_id === (int) $lecturer->id, 403);

        $session = GuidanceSession::create([
            'internship_id' => $internship->id,
            'held_on' => $data['held_on'],
            'start_time' => $data['start_time'],
            'mode' => $data['mode'],
            'meeting_link' => filled($data['meeting_link'] ?? null) ? trim($data['meeting_link']) : null,
            'topic' => trim($data['topic']),
            'notes' => filled($data['notes'] ?? null) ? trim($data['notes']) : null,
            'status' => 'scheduled',
            'created_by' => $lecturer->id,
        ]);

        $when = $session->held_on->locale('id')->translatedFormat('l, j F Y').' pukul '.$session->timeLabel();
        ActivityLog::record('bimbingan.jadwal', "Menjadwalkan bimbingan {$internship->student->name}: {$session->topic}", $session);
        Notify::send($internship->student, 'Jadwal bimbingan: '.$session->topic, $when.($session->meeting_link ? '. Gabung lewat tautan rapat di halaman Bimbingan.' : '.'), route('bimbingan.index'), 'calendar', 'blue', email: true);

        return $session;
    }

    public function decide(User $lecturer, GuidanceSession $session, bool $confirm, ?string $note): GuidanceSession
    {
        if (! in_array($session->status, ['pending', 'scheduled'], true)) {
            throw ValidationException::withMessages(['keputusan' => 'Bimbingan ini sudah diputuskan.']);
        }
        $wasScheduled = $session->status === 'scheduled';
        if ($wasScheduled && $confirm && $session->held_on->isFuture()) {
            throw ValidationException::withMessages(['keputusan' => 'Bimbingan baru bisa ditandai selesai setelah berlangsung.']);
        }
        if (! $wasScheduled && ! $confirm && ! filled($note)) {
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
        $title = match (true) {
            $confirm => "Bimbingan dikonfirmasi ({$count} dari {$min})",
            $wasScheduled => 'Jadwal bimbingan dibatalkan',
            default => 'Catatan bimbingan ditolak',
        };
        Notify::send($internship->student, $title, $note ?? ($wasScheduled ? $session->topic : null), route('bimbingan.index'), 'users', $confirm ? 'green' : 'red');

        return $session;
    }
}

<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WeeklyTask;
use Illuminate\Auth\Access\Response;

/**
 * Siapa boleh melakukan apa pada satu task mingguan.
 */
class WeeklyTaskPolicy
{
    /** Task dilihat oleh mahasiswa pemiliknya atau mentornya. */
    public function view(User $user, WeeklyTask $task): Response
    {
        $internship = $task->internship;

        $isOwner = $user->role === 'mahasiswa' && (int) $internship->student_id === (int) $user->id;
        $isMentor = $user->role === 'mentor' && $internship->isMentoredBy($user);

        return $isOwner || $isMentor
            ? Response::allow()
            : Response::deny('Anda tidak punya akses ke task ini.');
    }

    /** Mentor mengubah status dan memberi komentar pada task bimbingannya (selama KP belum dikunci). */
    public function update(User $user, WeeklyTask $task): Response
    {
        return (new InternshipPolicy)->manageTasks($user, $task->internship);
    }

    /** Mahasiswa melampirkan bukti hanya pada task miliknya (selama KP belum dikunci). */
    public function submitEvidence(User $user, WeeklyTask $task): Response
    {
        $internship = $task->internship;

        if ($user->role !== 'mahasiswa' || (int) $internship->student_id !== (int) $user->id) {
            return Response::deny('Task ini bukan milik Anda.');
        }

        if ($internship->isLocked()) {
            return Response::deny('KP Anda sudah selesai, data hanya bisa dibaca.');
        }

        return Response::allow();
    }
}

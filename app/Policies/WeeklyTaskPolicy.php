<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WeeklyTask;
use Illuminate\Auth\Access\Response;

/**
 * Siapa boleh melakukan apa pada satu task kegiatan.
 * Data kegiatan bersifat privat: hanya mahasiswa pemilik, Company Supervisor-nya, dosen pembimbingnya, dan kaprodi.
 */
class WeeklyTaskPolicy
{
    public function view(User $user, WeeklyTask $task): Response
    {
        $internship = $task->internship;

        $allowed = match ($user->role) {
            'mahasiswa' => (int) $internship->student_id === (int) $user->id,
            'supervisor' => $internship->isSupervisedBy($user),
            'dospem' => (int) $internship->lecturer_id === (int) $user->id,
            'kaprodi' => true,
            default => false,
        };

        return $allowed
            ? Response::allow()
            : Response::deny('Anda tidak punya akses ke task ini.');
    }

    /** Mahasiswa mengubah task miliknya (isi, kolom, checklist, waktu) selama KP berjalan. */
    public function update(User $user, WeeklyTask $task): Response
    {
        return (new InternshipPolicy)->manageActivities($user, $task->internship);
    }

    /** Komentar: mahasiswa pemilik, Company Supervisor, dan dosen pembimbing, selama KP belum dikunci. */
    public function comment(User $user, WeeklyTask $task): Response
    {
        $internship = $task->internship;

        if ($internship->isLocked()) {
            return Response::deny('KP ini sudah selesai, data hanya bisa dibaca.');
        }

        $allowed = (int) $internship->student_id === (int) $user->id && $user->role === 'mahasiswa'
            || (new InternshipPolicy)->monitor($user, $internship)->allowed();

        return $allowed
            ? Response::allow()
            : Response::deny('Anda tidak dapat berkomentar pada task ini.');
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

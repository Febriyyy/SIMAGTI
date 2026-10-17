<?php

namespace App\Policies;

use App\Models\Internship;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Siapa boleh melakukan apa pada satu penempatan KP.
 * Satu-satunya tempat aturan akses penempatan ditulis.
 */
class InternshipPolicy
{
    /** Mentor boleh menambah/mengubah task hanya untuk bimbingannya, dan hanya selama KP belum dikunci. */
    public function manageTasks(User $user, Internship $internship): Response
    {
        if (! $internship->isMentoredBy($user)) {
            return Response::deny('Mahasiswa ini bukan bimbingan Anda.');
        }

        if ($internship->isLocked()) {
            return Response::deny('KP mahasiswa ini sudah selesai, data hanya bisa dibaca.');
        }

        return Response::allow();
    }

    /** Mentor mengatur meeting hanya untuk bimbingannya yang sedang berjalan. */
    public function scheduleMeeting(User $user, Internship $internship): Response
    {
        if (! $internship->isMentoredBy($user)) {
            return Response::deny('Ada mahasiswa yang bukan bimbingan Anda.');
        }

        if ($internship->status !== 'berjalan' || $internship->isLocked()) {
            return Response::deny('Meeting hanya bisa diatur untuk KP yang sedang berjalan.');
        }

        return Response::allow();
    }

    /** Mahasiswa boleh mengajukan KP mandiri hanya jika belum punya penempatan aktif. */
    public function submitIndependent(User $user): Response
    {
        if ($user->role !== 'mahasiswa') {
            return Response::deny('Hanya mahasiswa yang dapat mengajukan KP.');
        }

        $hasActive = Internship::where('student_id', $user->id)
            ->whereIn('status', Internship::ACTIVE_STATUSES)
            ->exists();

        return $hasActive
            ? Response::deny('Anda masih memiliki pengajuan atau penempatan KP yang aktif.')
            : Response::allow();
    }

    /** Hanya biro kerjasama yang memverifikasi perusahaan KP mandiri. */
    public function reviewAsBiro(User $user, Internship $internship): Response
    {
        return $user->role === 'biro'
            ? Response::allow()
            : Response::deny('Hanya Biro Kerjasama yang dapat memverifikasi perusahaan.');
    }

    /** Hanya kaprodi yang memberi persetujuan akhir KP mandiri. */
    public function reviewAsKaprodi(User $user, Internship $internship): Response
    {
        return $user->role === 'kaprodi'
            ? Response::allow()
            : Response::deny('Hanya Kaprodi yang dapat memberi persetujuan akhir.');
    }

    /** Daftar seluruh penempatan boleh dilihat kaprodi dan biro. */
    public function viewAny(User $user): Response
    {
        return $user->hasRole('kaprodi', 'biro')
            ? Response::allow()
            : Response::deny('Anda tidak memiliki akses ke daftar penempatan.');
    }

    /** Penempatan langsung (jalur lowongan kampus) dibuat kaprodi, sekaligus menunjuk dosen pembimbing. */
    public function create(User $user): Response
    {
        return $user->hasRole('kaprodi')
            ? Response::allow()
            : Response::deny('Hanya Kaprodi yang dapat membuat penempatan.');
    }

    /**
     * Satu penempatan boleh dilihat oleh pihak yang terkait dengannya.
     * Memakai scope Internship::visibleTo() supaya aturan "siapa melihat apa" hanya ada satu.
     */
    public function view(User $user, Internship $internship): Response
    {
        return Internship::visibleTo($user)->whereKey($internship->getKey())->exists()
            ? Response::allow()
            : Response::deny('Anda tidak punya akses ke penempatan ini.');
    }
}

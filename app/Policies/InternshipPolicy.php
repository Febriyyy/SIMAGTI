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
    /**
     * Mahasiswa menyusun kegiatannya sendiri (proyek, board, task, timesheet) selama KP berjalan.
     * Sejak v2 perusahaan tidak lagi membuat task.
     */
    public function manageActivities(User $user, Internship $internship): Response
    {
        if ($user->role !== 'mahasiswa' || (int) $internship->student_id !== (int) $user->id) {
            return Response::deny('Kegiatan ini bukan milik Anda.');
        }

        if ($internship->isLocked()) {
            return Response::deny('KP Anda sudah selesai, data hanya bisa dibaca.');
        }

        if ($internship->status !== 'berjalan') {
            return Response::deny('Kegiatan bisa disusun setelah pengajuan KP disetujui.');
        }

        return Response::allow();
    }

    /** Silabus disusun mahasiswa; terkunci selama menunggu persetujuan dan setelah disetujui. */
    public function manageSyllabus(User $user, Internship $internship): Response
    {
        $base = $this->manageActivities($user, $internship);
        if ($base->denied()) {
            return $base;
        }

        return in_array($internship->syllabus_status, ['draft', 'revision'], true)
            ? Response::allow()
            : Response::deny($internship->syllabus_status === 'approved'
                ? 'Silabus sudah disetujui. Ajukan perubahan dulu untuk mengubahnya.'
                : 'Silabus sedang menunggu persetujuan.');
    }

    /**
     * Pihak tempat KP yang memantau: Company Supervisor untuk magang, dosen pembimbing untuk seminar.
     * Dipakai untuk menyetujui silabus, mengatur meeting, dan mengisi penilaian.
     */
    public function review(User $user, Internship $internship): Response
    {
        if (! $internship->isReviewedBy($user)) {
            return Response::deny($internship->isSeminar()
                ? 'Hanya dosen pembimbing mahasiswa ini yang dapat meninjau.'
                : 'Mahasiswa ini bukan bimbingan Anda.');
        }

        return Response::allow();
    }

    public function reviewSyllabus(User $user, Internship $internship): Response
    {
        $base = $this->review($user, $internship);
        if ($base->denied()) {
            return $base;
        }

        return $internship->syllabus_status === 'submitted'
            ? Response::allow()
            : Response::deny('Tidak ada silabus yang menunggu persetujuan.');
    }

    /** Company Supervisor dan dosen pembimbing berkomentar pada kegiatan mahasiswa (pemantauan). */
    public function monitor(User $user, Internship $internship): Response
    {
        $allowed = $internship->isSupervisedBy($user)
            || ($user->role === 'dospem' && (int) $internship->lecturer_id === (int) $user->id);

        return $allowed
            ? Response::allow()
            : Response::deny('Mahasiswa ini bukan bimbingan Anda.');
    }

    /** Company Supervisor mengoreksi posisi magang mahasiswanya. */
    public function correctPosition(User $user, Internship $internship): Response
    {
        return $internship->isSupervisedBy($user) && ! $internship->isLocked()
            ? Response::allow()
            : Response::deny('Hanya Company Supervisor mahasiswa ini yang dapat mengubah posisi.');
    }

    /** Meeting diatur pihak yang memantau, hanya untuk KP yang sedang berjalan. */
    public function scheduleMeeting(User $user, Internship $internship): Response
    {
        if (! $internship->isReviewedBy($user)) {
            return Response::deny('Ada mahasiswa yang bukan bimbingan Anda.');
        }

        if ($internship->status !== 'berjalan' || $internship->isLocked()) {
            return Response::deny('Meeting hanya bisa diatur untuk KP yang sedang berjalan.');
        }

        return Response::allow();
    }

    /**
     * Penilaian akhir (sementara, sampai penilaian bulanan v2 selesai): diisi Company Supervisor
     * (atau dosen pembimbing untuk seminar), lalu divalidasi dosen pembimbing.
     */
    public function assess(User $user, Internship $internship): Response
    {
        if (! $internship->isReviewedBy($user)) {
            return Response::deny('Mahasiswa ini bukan bimbingan Anda.');
        }

        if (! in_array($internship->status, ['berjalan', 'selesai'], true)) {
            return Response::deny('Penilaian hanya untuk KP yang sedang berjalan atau sudah selesai.');
        }

        $status = $internship->assessment?->status;

        if ($status === 'validated') {
            return Response::deny('Penilaian sudah divalidasi dan terkunci.');
        }

        if ($status === 'submitted') {
            return Response::deny('Penilaian sedang menunggu validasi dosen pembimbing.');
        }

        return Response::allow();
    }

    /** Dosen pembimbing memvalidasi atau mengembalikan penilaian mahasiswa bimbingannya. */
    public function validateAssessment(User $user, Internship $internship): Response
    {
        if ($user->role !== 'dospem' || (int) $internship->lecturer_id !== (int) $user->id) {
            return Response::deny('Mahasiswa ini bukan bimbingan Anda.');
        }

        if ($internship->assessment?->status !== 'submitted') {
            return Response::deny('Tidak ada penilaian yang menunggu validasi.');
        }

        return Response::allow();
    }

    /** TU Fakultas mengaktifkan Company Supervisor dari data yang diisi mahasiswa. */
    public function activateSupervisor(User $user, Internship $internship): Response
    {
        if ($user->role !== 'tu') {
            return Response::deny('Hanya TU Fakultas yang dapat mengaktifkan Company Supervisor.');
        }

        if ($internship->program !== 'magang' || $internship->status !== 'berjalan') {
            return Response::deny('Penempatan ini belum disetujui atau bukan jalur magang.');
        }

        return Response::allow();
    }

    /** Mahasiswa boleh mengajukan KP hanya jika belum punya penempatan aktif (syarat semester dicek di Action). */
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
        return $user->hasRole('kaprodi', 'biro', 'tu')
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

<?php

namespace App\Support;

use App\Models\Internship;
use App\Models\InternshipApproval;
use App\Models\InternshipStaff;
use Carbon\CarbonInterface;

/**
 * Kemajuan KP satu mahasiswa, langkah demi langkah (poin 15: status pengajuan dibuat detail, bukan satu info).
 *
 * Setiap langkah: key, judul, siapa yang memproses, keadaan, tanggal, keterangan, catatan.
 * Keadaan: done (selesai) · current (sedang berjalan, giliran mahasiswa) · waiting (menunggu pihak lain)
 *          · attention (ditolak / perlu revisi) · upcoming (belum sampai) · later (fitur tahap berikutnya)
 */
class KpProgress
{
    /** @return list<array{key: string, title: string, actor: string, state: string, date: ?CarbonInterface, detail: ?string, note: ?string}> */
    public static function steps(Internship $internship): array
    {
        $internship->loadMissing(['approvals.reviewer', 'lecturer', 'supervisor', 'staff', 'invitations']);

        $steps = [];
        $blocked = false; // setelah ada langkah yang ditolak/belum selesai, langkah berikutnya "upcoming"

        $add = function (string $key, string $title, string $actor, string $state, ?CarbonInterface $date = null, ?string $detail = null, ?string $note = null) use (&$steps, &$blocked) {
            if ($blocked && in_array($state, ['current', 'waiting', 'attention'], true)) {
                $state = 'upcoming';
            }
            $steps[] = compact('key', 'title', 'actor', 'state', 'date', 'detail', 'note');
            if ($state !== 'done') {
                $blocked = true;
            }
        };

        // 1. Pengajuan
        $add(
            'submitted',
            $internship->track === 'lowongan' ? 'Penempatan dibuat kampus' : 'Pengajuan dikirim',
            $internship->track === 'lowongan' ? 'Kaprodi' : 'Mahasiswa',
            'done',
            $internship->created_at,
            $internship->programLabel().' · '.$internship->programTypeLabel().' · '.$internship->placeName(),
        );

        $approval = fn (string $step) => $internship->approvals->firstWhere('step', $step);
        $fromApproval = fn (?InternshipApproval $a) => match ($a?->status) {
            'approved' => 'done',
            'rejected' => 'attention',
            default => 'waiting',
        };

        // 2. Verifikasi biro (magang, pengajuan mahasiswa)
        if ($internship->track === 'mandiri' && ! $internship->isSeminar()) {
            $a = $approval(InternshipApproval::STEP_BIRO);
            $add(
                'biro',
                'Verifikasi perusahaan',
                'Biro Kerjasama',
                $fromApproval($a),
                $a?->reviewed_at,
                match ($a?->status) {
                    'approved' => $a->reviewer ? 'Perusahaan terverifikasi oleh '.$a->reviewer->name : 'Perusahaan sudah terverifikasi sebelumnya',
                    'rejected' => 'Perusahaan tidak dapat diverifikasi',
                    default => 'Biro memeriksa data perusahaan',
                },
                $a?->note,
            );
        }

        // 3. Persetujuan kaprodi + dosen pembimbing
        if ($internship->track === 'mandiri') {
            $a = $approval(InternshipApproval::STEP_KAPRODI);
            $add(
                'kaprodi',
                'Persetujuan dan dosen pembimbing',
                'Kaprodi',
                $fromApproval($a),
                $a?->reviewed_at,
                match ($a?->status) {
                    'approved' => 'Dosen pembimbing: '.($internship->lecturer?->name ?? '–'),
                    'rejected' => 'Pengajuan tidak disetujui',
                    default => 'Kaprodi memeriksa pengajuan dan menunjuk dosen pembimbing',
                },
                $a?->note,
            );
        } else {
            $add('kaprodi', 'Dosen pembimbing ditunjuk', 'Kaprodi', $internship->lecturer ? 'done' : 'waiting', $internship->created_at,
                'Dosen pembimbing: '.($internship->lecturer?->name ?? 'belum ditunjuk'));
        }

        // 4. Company Supervisor (magang)
        if (! $internship->isSeminar()) {
            $staff = $internship->staff->firstWhere('role', InternshipStaff::ROLE_SUPERVISOR);
            $invitation = $internship->invitations->sortByDesc('id')->first();

            $who = ($internship->supervisor?->name ?? '–').($internship->supervisor_title ? ' · '.$internship->supervisor_title : '');
            if ($staff && $internship->supervisor_confirmation === 'confirmed') {
                $add('supervisor', 'Company Supervisor aktif', 'TU Fakultas', 'done', $internship->supervisor_confirmed_at ?? $staff->created_at,
                    $who.' · penempatan dikonfirmasi');
            } elseif ($staff && $internship->supervisor_confirmation === 'disputed') {
                $add('supervisor', 'Data penempatan dipertanyakan supervisor', 'TU Fakultas', 'attention', $internship->supervisor_confirmed_at,
                    $who.' · TU Fakultas menindaklanjuti', $internship->supervisor_confirmation_note);
            } elseif ($staff) {
                // Kegiatan tetap boleh berjalan selama menunggu konfirmasi satu klik dari supervisor
                $add('supervisor', 'Konfirmasi penempatan', 'Company Supervisor', 'waiting', $staff->created_at,
                    $who.' · akun aktif, menunggu konfirmasi data penempatan');
            } else {
                $add('supervisor', 'Aktivasi Company Supervisor', 'TU Fakultas', 'waiting', null,
                    $invitation && $invitation->isUsable()
                        ? 'Undangan sudah dikirim ke '.$invitation->email.', menunggu supervisor mengaktifkan akun'
                        : 'TU Fakultas memeriksa data supervisor ('.($internship->supervisor_email ?? 'email belum diisi').')');
            }
        }

        // 5. Silabus (berjalan bersamaan dengan pelaksanaan: kegiatan boleh dicatat sebelum silabus disetujui)
        $blockedBeforeSyllabus = $blocked;
        $reviewer = $internship->isSeminar() ? 'Dosen pembimbing' : 'Company Supervisor';
        $add(
            'syllabus',
            'Silabus disetujui',
            'Mahasiswa → '.$reviewer,
            match ($internship->syllabus_status) {
                'approved' => 'done',
                'submitted' => 'waiting',
                'revision' => 'attention',
                default => 'current',
            },
            $internship->syllabus_reviewed_at,
            match ($internship->syllabus_status) {
                'approved' => 'Disetujui oleh '.$reviewer,
                'submitted' => 'Menunggu persetujuan '.$reviewer,
                'revision' => 'Perlu revisi dari '.$reviewer,
                default => 'Susun tujuan hard skill, soft skill, dan attitude lalu kirim',
            },
            $internship->syllabus_status === 'revision' ? $internship->syllabus_note : null,
        );

        // 6. Pelaksanaan
        $blocked = $blockedBeforeSyllabus;
        $hours = Num::short($internship->loggedHours());
        $add(
            'activities',
            'Pelaksanaan kegiatan',
            'Mahasiswa',
            $internship->status === 'selesai' ? 'done' : 'current',
            $internship->status === 'selesai' ? $internship->end_date : null,
            $internship->status === 'berjalan'
                ? 'Minggu ke-'.$internship->currentWeek().' dari '.$internship->totalWeeks().' · '.$hours.' jam tercatat'
                : $internship->periodLabel(),
        );

        $running = in_array($internship->status, ['berjalan', 'selesai'], true);

        // 7. Bimbingan dengan dosen pembimbing (minimal sesuai aturan kampus)
        $min = (int) Campus::rule('min_guidance');
        $guided = $running ? $internship->confirmedGuidanceCount() : 0;
        $steps[] = ['key' => 'guidance', 'title' => 'Bimbingan dosen pembimbing', 'actor' => 'Mahasiswa → Dosen pembimbing',
            'state' => ! $running ? 'upcoming' : ($guided >= $min || $internship->status === 'selesai' ? 'done' : 'current'),
            'date' => null, 'detail' => "{$guided} dari minimal {$min} sesi terkonfirmasi", 'note' => null];

        // 8–9. Laporan per bab dan presentasi dikerjakan di tahap 3
        $later = $internship->status !== 'selesai';
        $steps[] = ['key' => 'report', 'title' => 'Laporan akhir per bab', 'actor' => 'Mahasiswa → '.$reviewer, 'state' => $later ? 'later' : 'done', 'date' => null, 'detail' => 'Bab yang memuat data perusahaan disetujui '.$reviewer, 'note' => null];
        $steps[] = ['key' => 'presentation', 'title' => 'Presentasi di depan dosen pembimbing', 'actor' => 'Dosen pembimbing', 'state' => $later ? 'later' : 'done', 'date' => null, 'detail' => 'Maksimal 10 menit, setelah disetujui maju presentasi', 'note' => null];

        // 10. Hasil KP
        $rule = $internship->isMbkm() ? 'Nilai angka dan huruf (60% mitra + 40% dosen)' : 'Diterima atau tidak diterima';
        $steps[] = ['key' => 'result', 'title' => 'Hasil KP', 'actor' => 'Dosen pembimbing',
            'state' => $internship->result ? ($internship->result === 'tidak' ? 'attention' : 'done') : ($later ? 'later' : 'done'),
            'date' => $internship->result_at ?? ($internship->status === 'selesai' ? $internship->locked_at : null),
            'detail' => $internship->result ? 'Hasil: '.$internship->resultLabel() : $rule,
            'note' => $internship->result ? $internship->result_note : null];

        if ($internship->status === 'ditolak') {
            foreach ($steps as &$step) {
                if (in_array($step['state'], ['upcoming', 'later', 'current', 'waiting'], true)) {
                    $step['state'] = 'upcoming';
                }
            }
        }

        return $steps;
    }

    /** Langkah yang sedang berjalan (untuk ringkasan "langkah berikutnya"). */
    public static function current(Internship $internship): ?array
    {
        foreach (self::steps($internship) as $step) {
            if (in_array($step['state'], ['current', 'waiting', 'attention'], true)) {
                return $step;
            }
        }

        return null;
    }
}

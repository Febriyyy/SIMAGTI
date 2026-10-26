<?php

namespace App\Support;

use App\Models\Internship;
use App\Models\InternshipApproval;
use App\Models\InternshipDocument;
use App\Models\InternshipStaff;
use Carbon\CarbonInterface;

/**
 * Kemajuan KP satu mahasiswa, langkah demi langkah (poin 15: status pengajuan dibuat detail, bukan satu info).
 *
 * Setiap langkah: key, judul, siapa yang memproses, keadaan, tanggal, keterangan, catatan,
 * url (halaman mahasiswa yang terkait, bisa diklik) dan cta (label tombol langkah berikutnya).
 * Keadaan: done (selesai) · current (sedang berjalan, giliran mahasiswa) · waiting (menunggu pihak lain)
 *          · attention (ditolak / perlu tindakan) · upcoming (belum sampai) · later (fitur tahap berikutnya)
 */
class KpProgress
{
    /** @return list<array{key: string, title: string, actor: string, state: string, date: ?CarbonInterface, detail: ?string, note: ?string, url: ?string, cta: ?string}> */
    public static function steps(Internship $internship): array
    {
        $internship->loadMissing(['approvals.reviewer', 'lecturer', 'supervisor', 'staff', 'invitations', 'documents', 'assessment']);

        $steps = [];
        $blocked = false; // setelah ada langkah yang ditolak/belum selesai, langkah berikutnya "upcoming"

        $add = function (string $key, string $title, string $actor, string $state, ?CarbonInterface $date = null, ?string $detail = null, ?string $note = null, ?string $url = null, ?string $cta = null) use (&$steps, &$blocked) {
            if ($blocked && in_array($state, ['current', 'waiting', 'attention'], true)) {
                $state = 'upcoming';
            }
            $steps[] = compact('key', 'title', 'actor', 'state', 'date', 'detail', 'note', 'url', 'cta');
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
            $internship->placeName().($internship->position ? ' · '.$internship->position : ''),
        );

        $approval = fn (string $step) => $internship->approvals->firstWhere('step', $step);
        $fromApproval = fn (?InternshipApproval $a) => match ($a?->status) {
            'approved' => 'done',
            'rejected' => 'attention',
            default => 'waiting',
        };

        // 2. Verifikasi TU Fakultas (pengajuan mahasiswa)
        if ($internship->track === 'mandiri') {
            $a = $approval(InternshipApproval::STEP_VERIFY);
            $add(
                'verify',
                'Verifikasi perusahaan',
                'TU Fakultas',
                $fromApproval($a),
                $a?->reviewed_at,
                match ($a?->status) {
                    'approved' => $a->reviewer ? 'Perusahaan terverifikasi oleh '.$a->reviewer->name : 'Perusahaan sudah terverifikasi sebelumnya',
                    'rejected' => 'Perusahaan tidak dapat diverifikasi',
                    default => 'TU Fakultas memeriksa data perusahaan',
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

        // 4. Company Supervisor
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

        // Langkah pelaksanaan berjalan bersamaan: tidak menunggu konfirmasi supervisor
        $running = in_array($internship->status, ['berjalan', 'selesai'], true);
        $blocked = ! $running;

        // 5. Dokumen KP (KP mandiri): surat balasan perusahaan, boleh menyusul sampai batas waktu, diperiksa TU Fakultas
        $reply = $internship->documents->firstWhere('type', InternshipDocument::REPLY);
        if ($internship->track === 'mandiri') {
            $add(
                'documents',
                'Surat balasan perusahaan',
                'Mahasiswa → TU Fakultas',
                match ($reply?->status) {
                    'diterima' => 'done',
                    'diunggah' => 'waiting',
                    'ditolak' => 'attention',
                    default => 'current',
                },
                $reply?->processed_at,
                match ($reply?->status) {
                    'diterima' => 'Diterima TU Fakultas',
                    'diunggah' => 'Menunggu diperiksa TU Fakultas',
                    'ditolak' => 'Perlu diunggah ulang',
                    default => $reply?->due_on ? 'Unggah paling lambat '.$reply->due_on->locale('id')->translatedFormat('j F Y') : 'Unggah surat balasan dari perusahaan',
                },
                $reply?->status === 'ditolak' ? $reply->note : null,
                route('dokumen.index'),
                'Buka dokumen',
            );
        }
        $blocked = ! $running; // dokumen boleh menyusul: tidak menahan langkah berikutnya

        // 6. Rencana kegiatan (timesheet / Gantt): diisi dulu sebelum membuat task
        $plans = $running ? $internship->activityPlans()->count() : 0;
        $add(
            'plans',
            'Rencana kegiatan',
            'Mahasiswa',
            $plans > 0 ? 'done' : 'current',
            null,
            $plans > 0 ? $plans.' rencana kegiatan di timesheet' : 'Susun rencana kegiatan per minggu atau per bulan',
            null,
            route('timesheet.index'),
            'Buka timesheet',
        );

        // 7. Pelaksanaan
        $total = $running ? $internship->tasks()->count() : 0;
        $done = $running ? $internship->tasks()->where('status', 'completed')->count() : 0;
        $add(
            'activities',
            'Pelaksanaan kegiatan',
            'Mahasiswa',
            $internship->status === 'selesai' ? 'done' : 'current',
            $internship->status === 'selesai' ? $internship->end_date : null,
            $internship->status === 'berjalan'
                ? 'Minggu ke-'.$internship->currentWeek().' dari '.$internship->totalWeeks().' · '.$done.'/'.$total.' task selesai'
                : $internship->periodLabel(),
            null,
            route('kegiatan.index'),
            'Buka kegiatan',
        );

        // 8. Bimbingan dengan dosen pembimbing (minimal sesuai aturan kampus)
        $min = (int) Campus::rule('min_guidance');
        $guided = $running ? $internship->confirmedGuidanceCount() : 0;
        $steps[] = ['key' => 'guidance', 'title' => 'Bimbingan dosen pembimbing', 'actor' => 'Mahasiswa → Dosen pembimbing',
            'state' => ! $running ? 'upcoming' : ($guided >= $min || $internship->status === 'selesai' ? 'done' : 'current'),
            'date' => null, 'detail' => "{$guided} dari minimal {$min} sesi terkonfirmasi", 'note' => null,
            'url' => route('bimbingan.index'), 'cta' => 'Buka bimbingan'];

        // 9. Penilaian akhir Company Supervisor (di akhir periode KP), divalidasi dosen pembimbing
        $assessment = $internship->assessment;
        $ending = $running && $internship->daysLeft() <= 14;
        $steps[] = ['key' => 'assessment', 'title' => 'Penilaian akhir', 'actor' => 'Company Supervisor → Dosen pembimbing',
            'state' => match (true) {
                $assessment?->status === 'validated' => 'done',
                $assessment?->status === 'submitted' => 'waiting',
                $ending => 'waiting',
                default => 'upcoming',
            },
            'date' => $assessment?->validated_at ?? $assessment?->submitted_at,
            'detail' => match (true) {
                $assessment?->status === 'validated' => 'Nilai '.$assessment->letter().' · divalidasi dosen pembimbing',
                $assessment?->status === 'submitted' => 'Menunggu validasi dosen pembimbing',
                $ending => 'Company Supervisor mengisi penilaian di akhir periode KP',
                default => 'Diisi Company Supervisor di akhir periode KP',
            },
            'note' => null, 'url' => null, 'cta' => null];

        // 10–11. Laporan per bab dan presentasi dikerjakan di tahap 3
        $later = $internship->status !== 'selesai';
        $steps[] = ['key' => 'report', 'title' => 'Laporan akhir per bab', 'actor' => 'Mahasiswa → Company Supervisor', 'state' => $later ? 'later' : 'done', 'date' => null, 'detail' => 'Bab yang memuat data perusahaan disetujui Company Supervisor', 'note' => null, 'url' => null, 'cta' => null];
        $steps[] = ['key' => 'presentation', 'title' => 'Presentasi di depan dosen pembimbing', 'actor' => 'Dosen pembimbing', 'state' => $later ? 'later' : 'done', 'date' => null, 'detail' => 'Maksimal 10 menit, setelah disetujui maju presentasi', 'note' => null, 'url' => null, 'cta' => null];

        // 12. Hasil KP
        $steps[] = ['key' => 'result', 'title' => 'Hasil KP', 'actor' => 'Dosen pembimbing',
            'state' => $internship->result ? ($internship->result === 'tidak' ? 'attention' : 'done') : ($later ? 'later' : 'done'),
            'date' => $internship->result_at ?? ($internship->status === 'selesai' ? $internship->locked_at : null),
            'detail' => $internship->result ? 'Hasil: '.$internship->resultLabel().($internship->final_grade ? ' · nilai '.$internship->final_grade : '') : 'Diterima atau tidak diterima',
            'note' => $internship->result ? $internship->result_note : null, 'url' => null, 'cta' => null];

        if (! $running) {
            // Sebelum KP berjalan: langkah pelaksanaan belum bisa dibuka
            foreach ($steps as &$step) {
                if (in_array($step['key'], ['documents', 'plans', 'activities', 'guidance'], true)) {
                    $step['state'] = 'upcoming';
                    $step['url'] = null;
                }
            }
            unset($step);
        }

        if ($internship->status === 'ditolak') {
            foreach ($steps as &$step) {
                if (in_array($step['state'], ['upcoming', 'later', 'current', 'waiting'], true)) {
                    $step['state'] = 'upcoming';
                }
            }
            unset($step);
        }

        return $steps;
    }

    /**
     * Langkah berikutnya untuk ringkasan: yang perlu tindakan dulu, lalu giliran mahasiswa, lalu yang menunggu pihak lain.
     *
     * @param  list<array>|null  $steps  hasil steps() bila sudah dihitung
     */
    public static function current(Internship $internship, ?array $steps = null): ?array
    {
        $steps ??= self::steps($internship);

        foreach (['attention', 'current', 'waiting'] as $state) {
            foreach ($steps as $step) {
                if ($step['state'] === $state) {
                    return $step;
                }
            }
        }

        return null;
    }
}

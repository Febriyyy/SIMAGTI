<?php

namespace App\Actions\Reminders;

use App\Actions\Conversions\ManageConversion;
use App\Models\ConversionCandidate;
use App\Models\DefenseSchedule;
use App\Models\GuidanceSession;
use App\Models\Internship;
use App\Models\User;
use App\Models\WeeklyTask;
use App\Notifications\KpReminder;
use Illuminate\Support\Carbon;

/**
 * Pengingat otomatis harian (dijalankan perintah kp:pengingat, terjadwal 07.00).
 *
 * - Mahasiswa: 3 hari tidak mengisi catatan (hanya hari kerja, paling sering sekali per 3 hari);
 *   task belum selesai yang tenggatnya hari ini s.d. 2 hari lagi (sekali per task).
 * - Company Supervisor: penilaian akhir dibuka (H-14), lalu sisa <= 3 hari dan belum dikirim (masing-masing sekali).
 * - Dosen pembimbing: bimbingan menunggu konfirmasi lebih dari 3 hari (paling sering sekali per 3 hari).
 * - Mahasiswa: tawaran kerja belum dijawab, H-3 dan H-1 sebelum batas (masing-masing sekali); lewat batas menjadi kedaluwarsa.
 *
 * Pengingat yang sama tidak dikirim ulang: setiap pengingat punya kunci yang dicek di tabel notifications.
 *
 * Pengingat jadwal (perintah kp:pengingat-jadwal, tiap 15 menit; lihat upcoming()):
 * bimbingan terjadwal dan sidang KP diingatkan sehari sebelumnya dan sekitar satu jam sebelum mulai.
 */
class SendReminders
{
    public const IDLE_DAYS = 3;

    public const DUE_SOON_DAYS = 2;

    public const SUPERVISOR_LAST_DAYS = 3;

    public const GUIDANCE_WAIT_DAYS = 3;

    /** Pengingat "sebentar lagi" dikirim bila acara mulai dalam rentang ini (menit); jadwal perintah tiap 15 menit. */
    public const SOON_MINUTES = 75;

    /** @return array{idle: int, due: int, assessment: int, guidance: int, offers: int, expired: int} */
    public function handle(?Carbon $today = null): array
    {
        $today = ($today ?? Carbon::today())->copy()->startOfDay();
        $sent = ['idle' => 0, 'due' => 0, 'assessment' => 0, 'guidance' => 0, 'offers' => 0, 'expired' => 0];

        $running = Internship::query()
            ->where('status', 'berjalan')
            ->whereDate('start_date', '<=', $today)
            ->with(['student', 'supervisor', 'assessment'])
            ->get();

        foreach ($running as $internship) {
            $sent['idle'] += $this->idle($internship, $today);
            $sent['due'] += $this->dueSoon($internship, $today);
            $sent['assessment'] += $this->assessment($internship, $today);
        }

        $sent['guidance'] = $this->guidance($today);
        $sent['offers'] = $this->offers($today);
        $sent['expired'] = ManageConversion::expireOverdue($today);

        return $sent;
    }

    /**
     * Pengingat bimbingan terjadwal dan sidang KP: H-1 (acara dalam 24 jam ke depan) dan menjelang mulai (<= SOON_MINUTES).
     * Bila acara sudah dekat, hanya pengingat menjelang mulai yang dikirim.
     *
     * @return array{guidance: int, defense: int}
     */
    public function upcoming(?Carbon $now = null): array
    {
        $now = ($now ?? Carbon::now())->copy();
        $sent = ['guidance' => 0, 'defense' => 0];

        $sessions = GuidanceSession::with('internship.student', 'internship.lecturer')
            ->where('status', 'scheduled')
            ->whereDate('held_on', '>=', $now->toDateString())
            ->whereDate('held_on', '<=', $now->copy()->addDay()->toDateString())
            ->get();
        foreach ($sessions as $s) {
            $stage = $this->stage($now, $s->startsAt());
            if (! $stage) {
                continue;
            }
            $when = $stage === 'soon' ? 'Mulai pukul '.$s->timeLabel() : 'Besok pukul '.$s->timeLabel();
            if ($stage === 'day' && $s->held_on->isSameDay($now)) {
                $when = 'Hari ini pukul '.$s->timeLabel();
            }
            $body = $when.' · '.$s->modeLabel().($s->meeting_link ? '. Gabung: '.$s->meeting_link : '');
            $title = ($stage === 'soon' ? 'Sebentar lagi: bimbingan ' : 'Pengingat bimbingan: ').$s->topic;
            $sent['guidance'] += $this->send($s->internship->student, "guidance-{$stage}:{$s->id}", null, $title, $body, route('bimbingan.index'), 'calendar', 'blue');
            if ($stage === 'soon' && $s->internship->lecturer) {
                $this->send($s->internship->lecturer, "guidance-{$stage}:{$s->id}", null, 'Sebentar lagi: bimbingan '.$s->internship->student->listName(), $body, route('bimbingan.antrean'), 'calendar', 'blue');
            }
        }

        $defenses = DefenseSchedule::with('internship.student', 'internship.lecturer', 'internship.defenseScore', 'examiner')
            ->whereBetween('scheduled_at', [$now, $now->copy()->addDay()])
            ->get()
            ->filter(fn (DefenseSchedule $d) => $d->internship->defenseScore === null);
        foreach ($defenses as $d) {
            $stage = $this->stage($now, $d->scheduled_at);
            if (! $stage) {
                continue;
            }
            $student = $d->internship->student;
            $body = $d->whenLabel().' · '.($d->isOnline() ? 'Daring: ' : 'Ruang ').$d->place;
            $prefix = $stage === 'soon' ? 'Sebentar lagi: ' : 'Pengingat: ';
            $sent['defense'] += $this->send($student, "defense-{$stage}:{$d->id}", null, $prefix.'sidang KP', $body, route('kp.status'), 'calendar', 'blue');
            foreach (collect([$d->internship->lecturer, $d->examiner])->filter()->unique('id') as $staff) {
                $this->send($staff, "defense-{$stage}:{$d->id}", null, $prefix.'sidang KP '.$student->listName(), $body, route('pantau.show', $d->internship), 'calendar', 'blue');
            }
        }

        return $sent;
    }

    /** 'soon' bila mulai dalam SOON_MINUTES menit, 'day' bila dalam 24 jam, null bila lewat atau masih jauh. */
    private function stage(Carbon $now, Carbon $start): ?string
    {
        $minutes = $now->diffInMinutes($start, false);

        return match (true) {
            $minutes <= 0 => null,
            $minutes <= self::SOON_MINUTES => 'soon',
            $minutes <= 24 * 60 => 'day',
            default => null,
        };
    }

    /** Mahasiswa belum mengisi catatan selama IDLE_DAYS hari (tidak dikirim Sabtu/Minggu). */
    private function idle(Internship $internship, Carbon $today): int
    {
        $student = $internship->student;
        if (! $student || $today->isWeekend() || $internship->start_date->gt($today->copy()->subDays(self::IDLE_DAYS))) {
            return 0;
        }

        $last = $internship->timeEntries()->max('work_date');
        if ($last && Carbon::parse($last)->gt($today->copy()->subDays(self::IDLE_DAYS))) {
            return 0;
        }

        $days = $last ? (int) Carbon::parse($last)->startOfDay()->diffInDays($today) : null;

        return $this->send($student, "idle:{$internship->id}", self::IDLE_DAYS,
            'Catatan kegiatan belum diisi',
            $days ? "Catatan terakhir {$days} hari lalu. Isi catatan hari ini agar kegiatan KP tetap tercatat." : 'Belum ada catatan kegiatan. Isi catatan hari ini agar kegiatan KP tercatat.',
            route('timesheet.index').'#catatan', 'edit');
    }

    /** Task belum selesai yang tenggatnya hari ini sampai DUE_SOON_DAYS hari lagi. */
    private function dueSoon(Internship $internship, Carbon $today): int
    {
        if (! $internship->student) {
            return 0;
        }

        $tasks = WeeklyTask::query()
            ->where('internship_id', $internship->id)
            ->where('status', '!=', 'completed')
            ->whereNotNull('due_date')
            ->whereDate('due_date', '>=', $today)
            ->whereDate('due_date', '<=', $today->copy()->addDays(self::DUE_SOON_DAYS))
            ->get();

        $count = 0;
        foreach ($tasks as $task) {
            $when = $task->due_date->isSameDay($today) ? 'hari ini' : $task->due_date->locale('id')->translatedFormat('l, j M');
            $count += $this->send($internship->student, "due:{$task->id}:{$task->due_date->toDateString()}", null,
                "Tenggat task {$task->key()} {$when}",
                $task->title,
                route('tugas.show', $task), 'flag');
        }

        return $count;
    }

    /** Company Supervisor: penilaian dibuka, lalu pengingat terakhir bila sisa hari sedikit dan belum dikirim. */
    private function assessment(Internship $internship, Carbon $today): int
    {
        $supervisor = $internship->supervisor;
        $status = $internship->assessment?->status;
        if (! $supervisor || in_array($status, ['submitted', 'validated'], true) || $today->lt($internship->assessmentOpensOn())) {
            return 0;
        }

        $name = $internship->student?->name ?? 'mahasiswa';
        $url = route('penilaian.edit', $internship);
        $daysLeft = (int) $today->diffInDays($internship->end_date->copy()->startOfDay(), false);

        if ($daysLeft <= self::SUPERVISOR_LAST_DAYS) {
            $when = $daysLeft <= 0 ? 'hari ini' : "{$daysLeft} hari lagi";

            return $this->send($supervisor, "assess-due:{$internship->id}", null,
                "Penilaian {$name} belum dikirim",
                "KP berakhir {$when}. Isi penilaian akhir agar dosen pembimbing bisa memvalidasi.",
                $url, 'award', 'red');
        }

        return $this->send($supervisor, "assess-open:{$internship->id}", null,
            "Penilaian akhir {$name} sudah dibuka",
            'KP berakhir '.$internship->end_date->locale('id')->translatedFormat('j F Y').'. Penilaian bisa diisi bertahap dan disimpan sebagai draf.',
            $url, 'award', 'blue');
    }

    /** Dosen pembimbing: bimbingan yang menunggu konfirmasi lebih dari GUIDANCE_WAIT_DAYS hari, dirangkum per dosen. */
    private function guidance(Carbon $today): int
    {
        $waiting = GuidanceSession::query()
            ->where('status', 'pending')
            ->where('created_at', '<=', $today->copy()->subDays(self::GUIDANCE_WAIT_DAYS)->endOfDay())
            ->whereHas('internship', fn ($q) => $q->whereNotNull('lecturer_id'))
            ->with('internship.lecturer')
            ->get()
            ->groupBy(fn (GuidanceSession $s) => $s->internship->lecturer_id);

        $count = 0;
        foreach ($waiting as $sessions) {
            $lecturer = $sessions->first()->internship->lecturer;
            if (! $lecturer) {
                continue;
            }
            $count += $this->send($lecturer, "guidance:{$lecturer->id}", self::GUIDANCE_WAIT_DAYS,
                $sessions->count().' bimbingan menunggu konfirmasi',
                'Sudah lebih dari '.self::GUIDANCE_WAIT_DAYS.' hari. Konfirmasi agar mahasiswa bisa memenuhi syarat bimbingan.',
                route('bimbingan.antrean'), 'chat');
        }

        return $count;
    }

    /**
     * Kirim bila pengingat dengan kunci yang sama belum pernah dikirim (atau sudah lebih dari $everyDays hari lalu).
     * $everyDays null = hanya sekali.
     */
    /** Tawaran kerja belum dijawab: pengingat ke mahasiswa H-3 dan H-1 sebelum batas jawaban. */
    private function offers(Carbon $today): int
    {
        $sent = 0;
        $candidates = ConversionCandidate::with('internship.student')->where('status', 'ditawari')->whereNotNull('respond_by')
            ->whereDate('respond_by', '>=', $today)->whereDate('respond_by', '<=', $today->copy()->addDays(3))->get();

        foreach ($candidates as $candidate) {
            $days = (int) $today->diffInDays($candidate->respond_by->copy()->startOfDay());
            if (! in_array($days, [3, 1], true) || ! $candidate->internship?->student) {
                continue;
            }
            $sent += $this->send($candidate->internship->student, "offer:{$candidate->id}:{$days}", null,
                'Tawaran kerja menunggu jawaban Anda',
                'Batas jawaban '.$candidate->respond_by->locale('id')->translatedFormat('j F Y').' ('.$days.' hari lagi).',
                route('rekrutmen.tawaran'), 'star');
        }

        return $sent;
    }

    private function send(User $user, string $key, ?int $everyDays, string $title, ?string $body, ?string $url, string $icon, string $tone = 'amber'): int
    {
        if (! $user->is_active) {
            return 0;
        }

        $already = $user->notifications()
            ->where('data', 'like', '%"key":"'.$key.'"%')
            ->when($everyDays !== null, fn ($q) => $q->where('created_at', '>', now()->subDays($everyDays)->addHour()))
            ->exists();
        if ($already) {
            return 0;
        }

        $user->notify(new KpReminder($key, $title, $body, $url, $icon, $tone));

        return 1;
    }
}

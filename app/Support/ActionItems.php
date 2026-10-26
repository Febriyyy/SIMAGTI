<?php

namespace App\Support;

use App\Models\Assessment;
use App\Models\ChangeRequest;
use App\Models\GuidanceSession;
use App\Models\Internship;
use App\Models\InternshipApproval;
use App\Models\InternshipDocument;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Daftar "perlu tindakan" di lonceng notifikasi. Dihitung langsung dari keadaan data,
 * jadi item hilang sendiri begitu ditindaklanjuti (tidak ada notifikasi basi).
 *
 * Satu item: title, detail, url, icon, tone (amber = perlu tindakan, red = terlambat).
 */
class ActionItems
{
    /** @return list<array{title: string, detail: ?string, url: string, icon: string, tone: string}> */
    public static function for(User $user): array
    {
        $items = match ($user->role) {
            'mahasiswa' => self::student($user),
            'supervisor' => self::supervisor($user),
            'dospem' => self::lecturer($user),
            'kaprodi' => self::kaprodi(),
            'tu' => self::tu(),
            default => [],
        };

        // Yang terlambat (merah) di atas
        usort($items, fn ($a, $b) => ($a['tone'] === 'red' ? 0 : 1) <=> ($b['tone'] === 'red' ? 0 : 1));

        return $items;
    }

    private static function item(string $title, string $url, ?string $detail = null, string $icon = 'alert', string $tone = 'amber'): array
    {
        return compact('title', 'detail', 'url', 'icon', 'tone');
    }

    // ---------------------------------------------------------------- Mahasiswa

    private static function student(User $user): array
    {
        $internship = Internship::where('student_id', $user->id)->where('status', 'berjalan')->first();
        if (! $internship) {
            return [];
        }

        $items = [];

        // Rencana kegiatan diisi dulu sebelum membuat task
        if (! $internship->activityPlans()->exists()) {
            $items[] = self::item('Isi rencana kegiatan', route('timesheet.index'), 'Task dibuat di bawah rencana', 'calendar');
        }

        // Catatan kegiatan: hari kerja terakhir (maks. 3) yang belum diisi
        $workdays = collect();
        for ($d = Carbon::yesterday(); $workdays->count() < 3 && $d->gte($internship->start_date); $d->subDay()) {
            if (! $d->isWeekend()) {
                $workdays->push($d->toDateString());
            }
        }
        if ($workdays->isNotEmpty()) {
            $filled = $internship->timeEntries()->whereIn('work_date', $workdays->all())->pluck('work_date')
                ->map(fn ($d) => Carbon::parse($d)->toDateString())->unique();
            $missing = $workdays->diff($filled)->count();
            if ($missing > 0) {
                $items[] = self::item("Catatan kegiatan {$missing} hari kerja terakhir belum diisi", route('timesheet.index'), null, 'clock', $missing >= 3 ? 'red' : 'amber');
            }
        }

        // Dokumen
        foreach ($internship->documents()->whereIn('status', ['menunggu', 'ditolak'])->get() as $doc) {
            $items[] = self::item(
                ($doc->status === 'ditolak' ? 'Unggah ulang ' : 'Unggah ').mb_strtolower($doc->label()),
                route('dokumen.index'),
                $doc->due_on ? 'Batas '.$doc->due_on->locale('id')->translatedFormat('j F Y') : null,
                'file',
                $doc->isOverdue() || $doc->status === 'ditolak' ? 'red' : 'amber'
            );
        }

        // Bimbingan: kurang dari minimal ketika KP tinggal 30 hari
        $min = (int) Campus::rule('min_guidance');
        $count = $internship->confirmedGuidanceCount();
        if ($count < $min && $internship->daysLeft() <= 30) {
            $items[] = self::item("Bimbingan baru {$count} dari {$min}", route('bimbingan.index'), 'Minimal sebelum hasil KP diputuskan', 'users', 'red');
        }

        return $items;
    }

    // ---------------------------------------------------------------- Company Supervisor

    private static function supervisor(User $user): array
    {
        $internships = Internship::supervisedBy($user)->with(['student', 'assessment'])->where('status', 'berjalan')->get();
        $items = [];

        $unconfirmed = $internships->whereNull('supervisor_confirmation');
        if ($unconfirmed->isNotEmpty()) {
            $items[] = self::item('Konfirmasi penempatan '.$unconfirmed->count().' mahasiswa', route('dashboard').'#konfirmasi',
                $unconfirmed->pluck('student.name')->join(', '), 'verify');
        }

        $ids = $internships->pluck('id');

        $changes = ChangeRequest::with('internship.student')->whereIn('internship_id', $ids)->where('status', 'pending')->where('step', 'supervisor')->get();
        foreach ($changes as $c) {
            $items[] = self::item($c->typeLabel().' '.$c->internship->student->name, route('perubahan.antrean'), $c->summary(), 'history');
        }

        // Penilaian akhir di akhir periode KP: tinggal 14 hari atau sudah lewat, penilaian belum dikirim
        foreach ($internships as $i) {
            if ($i->daysLeft() <= 14 && ! in_array($i->assessment?->status, ['submitted', 'validated'], true)) {
                $items[] = self::item('Penilaian akhir '.$i->student->name, route('penilaian.edit', $i),
                    $i->daysLeft() > 0 ? 'KP selesai '.$i->daysLeft().' hari lagi' : 'KP sudah berakhir', 'award', $i->daysLeft() <= 0 ? 'red' : 'amber');
            }
        }

        return $items;
    }

    // ---------------------------------------------------------------- Dosen pembimbing

    private static function lecturer(User $user): array
    {
        $internships = Internship::with(['student', 'assessment'])->where('lecturer_id', $user->id)->where('status', 'berjalan')->get();
        $ids = $internships->pluck('id');
        $items = [];

        foreach (Assessment::with('internship.student')->whereIn('internship_id', $ids)->where('status', 'submitted')->get() as $a) {
            $items[] = self::item('Validasi penilaian '.$a->internship->student->name, route('validasi.show', $a->internship), null, 'verify');
        }

        $guidance = GuidanceSession::whereIn('internship_id', $ids)->where('status', 'pending')->count();
        if ($guidance > 0) {
            $items[] = self::item("{$guidance} bimbingan menunggu konfirmasi", route('bimbingan.antrean'), null, 'users');
        }

        foreach ($internships as $i) {
            if ($i->daysLeft() <= 0 && $i->result === null && $i->final_grade === null) {
                $items[] = self::item('Putuskan hasil KP '.$i->student->name, route('hasil.edit', $i), 'Periode KP sudah berakhir', 'award', 'red');
            }
        }

        return $items;
    }

    // ---------------------------------------------------------------- Kampus

    private static function kaprodi(): array
    {
        $items = [];
        $waiting = Internship::awaitingKaprodi()->count();
        if ($waiting > 0) {
            $items[] = self::item("{$waiting} pengajuan KP menunggu persetujuan", route('kaprodi.persetujuan'), null, 'approval');
        }
        $changes = ChangeRequest::where('status', 'pending')->where('step', 'kaprodi')->count();
        if ($changes > 0) {
            $items[] = self::item("{$changes} pengajuan perubahan menunggu", route('perubahan.antrean'), null, 'history');
        }

        return $items;
    }

    private static function tu(): array
    {
        $items = [];
        $verify = Internship::where('status', 'diajukan')->whereHas('approvals', fn ($q) => $q->where('step', InternshipApproval::STEP_VERIFY)->where('status', 'pending'))->count();
        if ($verify > 0) {
            $items[] = self::item("{$verify} KP mandiri menunggu verifikasi", route('tu.verifikasi'), 'Periksa data perusahaan', 'verify');
        }
        $uploaded = InternshipDocument::where('type', InternshipDocument::REPLY)->where('status', 'diunggah')->count();
        if ($uploaded > 0) {
            $items[] = self::item("{$uploaded} surat balasan perlu diperiksa", route('tu.dokumen'), null, 'file');
        }
        $overdue = InternshipDocument::whereIn('status', ['menunggu', 'ditolak'])->whereNotNull('due_on')->where('due_on', '<', Carbon::today()->toDateString())->count();
        if ($overdue > 0) {
            $items[] = self::item("{$overdue} dokumen lewat batas waktu", route('tu.dokumen'), null, 'alert', 'red');
        }
        $groups = Internship::awaitingSupervisor()->get()->groupBy(fn ($i) => $i->company_id.'|'.$i->supervisor_email)->count();
        if ($groups > 0) {
            $items[] = self::item("{$groups} Company Supervisor menunggu aktivasi", route('tu.aktivasi'), null, 'verify');
        }
        $disputed = Internship::where('status', 'berjalan')->where('supervisor_confirmation', 'disputed')->count();
        if ($disputed > 0) {
            $items[] = self::item("{$disputed} penempatan disanggah supervisor", route('tu.aktivasi').'#disanggah', null, 'alert', 'red');
        }
        $letters = InternshipDocument::where('type', InternshipDocument::LETTER)->where('status', 'diminta')->count();
        if ($letters > 0) {
            $items[] = self::item("{$letters} permintaan surat pengantar", route('tu.surat'), null, 'mail');
        }
        $changes = ChangeRequest::where('status', 'pending')->where('step', 'tu')->count();
        if ($changes > 0) {
            $items[] = self::item("{$changes} permintaan ganti supervisor", route('perubahan.antrean'), null, 'history');
        }

        return $items;
    }
}

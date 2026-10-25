<?php

namespace App\Support;

use App\Models\Assessment;
use App\Models\ChangeRequest;
use App\Models\CreditRecognition;
use App\Models\GuidanceSession;
use App\Models\Internship;
use App\Models\InternshipDocument;
use App\Models\MonthlyReview;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

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
            'biro' => self::biro(),
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

        if ($internship->syllabus_status === 'revision') {
            $items[] = self::item('Silabus perlu revisi', route('silabus.index'), $internship->syllabus_note, 'syllabus', 'red');
        } elseif ($internship->syllabus_status === 'draft') {
            $items[] = self::item('Lengkapi dan kirim silabus', route('silabus.index'), 'Tujuan hard skill, soft skill, dan attitude', 'syllabus');
        }

        // Timesheet: hari kerja terakhir (maks. 3) yang belum diisi
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
                $items[] = self::item("Timesheet {$missing} hari kerja terakhir belum diisi", route('timesheet.index'), null, 'clock', $missing >= 3 ? 'red' : 'amber');
            }
        }

        // Refleksi bulanan
        foreach (self::dueReflections($internship) as $month) {
            [, $end] = $internship->monthRange($month);
            $late = $end->copy()->addDays((int) Campus::rule('reflection_grace_days'))->isPast();
            $items[] = self::item("Isi refleksi bulan ke-{$month}", route('bulanan.index'), $end->isPast() ? 'Bulan sudah berakhir' : 'Bulan segera berakhir', 'history', $late ? 'red' : 'amber');
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

    /** Bulan KP yang refleksinya sudah bisa/harus diisi tapi belum. @return list<int> */
    private static function dueReflections(Internship $internship): array
    {
        $done = $internship->monthlyReviews()->whereNotNull('reflected_at')->pluck('month_number')->all();
        $due = [];
        for ($m = 1; $m <= $internship->monthCount(); $m++) {
            [, $end] = $internship->monthRange($m);
            if (Carbon::today()->lt($end->copy()->subDays(7))) {
                break;
            }
            if (! in_array($m, $done, true)) {
                $due[] = $m;
            }
        }

        return $due;
    }

    // ---------------------------------------------------------------- Company Supervisor

    private static function supervisor(User $user): array
    {
        $internships = Internship::supervisedBy($user)->with('student')->where('status', 'berjalan')->get();
        $items = [];

        $unconfirmed = $internships->whereNull('supervisor_confirmation');
        if ($unconfirmed->isNotEmpty()) {
            $items[] = self::item('Konfirmasi penempatan '.$unconfirmed->count().' mahasiswa', route('dashboard').'#konfirmasi',
                $unconfirmed->pluck('student.name')->join(', '), 'verify');
        }

        return array_merge($items, self::reviewerItems($internships));
    }

    /** Tugas peninjau (supervisor untuk magang, dosen untuk seminar): silabus, bulanan, perubahan, penilaian. */
    private static function reviewerItems(Collection $internships): array
    {
        $items = [];

        foreach ($internships->where('syllabus_status', 'submitted') as $i) {
            $items[] = self::item('Silabus '.$i->student->name.' menunggu persetujuan', route('pantau.show', [$i, 'tab' => 'silabus']), null, 'syllabus');
        }

        $ids = $internships->pluck('id');
        $pending = MonthlyReview::with('internship.student')->whereIn('internship_id', $ids)->whereNull('reviewed_at')
            ->where(fn ($q) => $q->whereNotNull('reflected_at')->orWhere('period_end', '<', Carbon::today()->toDateString()))
            ->get();
        foreach ($pending as $r) {
            $items[] = self::item('Nilai bulan ke-'.$r->month_number.' '.$r->internship->student->name, route('pantau.show', [$r->internship, 'tab' => 'bulanan']),
                $r->isReflected() ? 'Refleksi sudah masuk' : 'Bulan sudah berakhir', 'award', $r->period_end->copy()->addDays(14)->isPast() ? 'red' : 'amber');
        }

        // Bulan yang sudah berakhir tapi barisnya belum ada (mahasiswa belum membuka refleksi)
        foreach ($internships as $i) {
            $existing = $pending->where('internship_id', $i->id)->pluck('month_number')
                ->merge(MonthlyReview::where('internship_id', $i->id)->whereNotNull('reviewed_at')->pluck('month_number'))->all();
            for ($m = 1; $m <= $i->monthCount(); $m++) {
                [, $end] = $i->monthRange($m);
                if (! $end->isPast()) {
                    break;
                }
                if (! in_array($m, $existing, true)) {
                    $items[] = self::item('Nilai bulan ke-'.$m.' '.$i->student->name, route('pantau.show', [$i, 'tab' => 'bulanan']), 'Mahasiswa belum mengisi refleksi', 'award');
                }
            }
        }

        $changes = ChangeRequest::with('internship.student')->whereIn('internship_id', $ids)->where('status', 'pending')->where('step', 'supervisor')->get();
        foreach ($changes as $c) {
            $items[] = self::item($c->typeLabel().' '.$c->internship->student->name, route('perubahan.antrean'), $c->summary(), 'history');
        }

        // Penilaian akhir: KP tinggal 14 hari atau sudah lewat, penilaian belum dikirim
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
        $items = self::reviewerItems($internships->where('program', 'seminar'));

        foreach (Assessment::with('internship.student')->whereIn('internship_id', $ids)->where('status', 'submitted')->get() as $a) {
            $items[] = self::item('Validasi penilaian '.$a->internship->student->name, route('validasi.show', $a->internship), null, 'verify');
        }

        $guidance = GuidanceSession::whereIn('internship_id', $ids)->where('status', 'pending')->count();
        if ($guidance > 0) {
            $items[] = self::item("{$guidance} bimbingan menunggu konfirmasi", route('bimbingan.antrean'), null, 'users');
        }

        $notes = MonthlyReview::with('internship.student')->whereIn('internship_id', $internships->where('program', 'magang')->pluck('id'))
            ->whereNotNull('reviewed_at')->whereNull('lecturer_noted_at')->get();
        foreach ($notes as $r) {
            $items[] = self::item('Catatan bulan ke-'.$r->month_number.' '.$r->internship->student->name, route('pantau.show', [$r->internship, 'tab' => 'bulanan']),
                'Sudah dinilai supervisor', 'chat', 'amber');
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
        $credits = CreditRecognition::where('status', 'diajukan')->count();
        if ($credits > 0) {
            $items[] = self::item("{$credits} usulan rekognisi SKS menunggu", route('rekognisi.antrean'), null, 'school');
        }

        return $items;
    }

    private static function biro(): array
    {
        $items = [];
        $verify = Internship::where('status', 'diajukan')->whereHas('approvals', fn ($q) => $q->where('step', 'biro_verification')->where('status', 'pending'))->count();
        if ($verify > 0) {
            $items[] = self::item("{$verify} perusahaan menunggu verifikasi", route('biro.verifikasi'), null, 'verify');
        }
        $uploaded = InternshipDocument::whereIn('type', [InternshipDocument::REPLY, InternshipDocument::PACT])->where('status', 'diunggah')->count();
        if ($uploaded > 0) {
            $items[] = self::item("{$uploaded} dokumen perlu diperiksa", route('biro.dokumen'), 'Surat balasan dan pakta integritas', 'file');
        }
        $overdue = InternshipDocument::whereIn('status', ['menunggu', 'ditolak'])->whereNotNull('due_on')->where('due_on', '<', Carbon::today()->toDateString())->count();
        if ($overdue > 0) {
            $items[] = self::item("{$overdue} dokumen lewat batas waktu", route('biro.dokumen'), null, 'alert', 'red');
        }

        return $items;
    }

    private static function tu(): array
    {
        $items = [];
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

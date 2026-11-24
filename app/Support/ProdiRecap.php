<?php

namespace App\Support;

use App\Models\ConversionCandidate;
use App\Models\Internship;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Rekap KP tingkat prodi (Kaprodi dan TU Fakultas): satu baris per KP berisi status, nilai akhir,
 * nilai sidang, laporan per bab, dan rekrutmen. Dipakai halaman Rekap KP dan unduhan Excel.
 */
class ProdiRecap
{
    /** filter status => [label, status KP yang termasuk] */
    public const FILTERS = [
        'aktif' => ['Berjalan dan selesai', ['berjalan', 'selesai']],
        'berjalan' => ['Berjalan', ['berjalan']],
        'selesai' => ['Selesai', ['selesai']],
        'semua' => ['Semua status', ['diajukan', 'berjalan', 'selesai', 'ditolak', 'dibatalkan', 'pindah']],
    ];

    /** @return Collection<int, Internship> */
    public static function internships(string $filter = 'aktif', ?string $search = null, ?User $viewer = null, ?int $programId = null): Collection
    {
        $statuses = self::FILTERS[$filter][1] ?? self::FILTERS['aktif'][1];

        return Internship::with(['student.studentProfile.studyProgram', 'company', 'lecturer', 'assessment', 'defenseScore', 'conversion', 'reportChapters'])
            ->when($viewer, fn ($q) => $q->visibleTo($viewer))
            ->inProgram($programId)
            ->whereIn('status', $statuses)
            ->when(filled($search), fn ($q) => $q->whereHas('student', fn ($s) => $s->where('name', 'like', '%'.$search.'%')->orWhere('nim', 'like', '%'.$search.'%')))
            ->orderByRaw("CASE status WHEN 'berjalan' THEN 0 WHEN 'selesai' THEN 1 ELSE 2 END")
            ->orderBy('end_date')
            ->get();
    }

    /** Ringkasan angka untuk kartu di kepala halaman. @return array{total: int, done: int, average: ?float, hired: int} */
    public static function summary(Collection $internships): array
    {
        $scores = $internships->map(fn (Internship $i) => self::finalScore($i))->filter(fn ($s) => $s !== null);

        return [
            'total' => $internships->count(),
            'done' => $internships->where('status', 'selesai')->count(),
            'average' => $scores->isEmpty() ? null : round($scores->avg(), 2),
            'hired' => $internships->filter(fn (Internship $i) => $i->conversion?->status === 'diterima')->count(),
        ];
    }

    /** Nilai akhir yang sudah sah: dari hasil KP, atau penilaian yang sudah divalidasi dosen. */
    public static function finalScore(Internship $i): ?float
    {
        if ($i->final_score !== null) {
            return (float) $i->final_score;
        }

        return $i->assessment?->isValidated() ? (float) $i->assessment->final_score : null;
    }

    public static function finalGrade(Internship $i): ?string
    {
        return $i->final_grade ?? ($i->assessment?->isValidated() ? $i->assessment->letter_grade : null);
    }

    /** Laporan per bab: "2/4", atau null bila belum mulai. */
    public static function reportProgress(Internship $i): ?string
    {
        $chapters = $i->reportChapters;

        return $chapters->isEmpty() ? null : $chapters->where('status', 'disetujui')->count().'/'.$chapters->count();
    }

    public static function recruitmentLabel(Internship $i): ?string
    {
        $c = $i->conversion;

        return $c ? (ConversionCandidate::STATUSES[$c->status][0] ?? $c->status).($c->offered_position && $c->status !== 'direkomendasikan' ? ' · '.$c->offered_position : '') : null;
    }

    /** Berkas Excel rekap. */
    public static function xlsx(Collection $internships, string $filterLabel): string
    {
        $rows = [['No', 'NIM', 'Nama', 'Program studi', 'Perusahaan', 'Posisi', 'Dosen pembimbing', 'Periode', 'Status KP', 'Hasil KP',
            'Nilai akhir', 'Huruf', 'Nilai sidang', 'Huruf sidang', 'Tanggal sidang', 'Laporan disetujui', 'Rekrutmen']];

        foreach ($internships->values() as $n => $i) {
            $rows[] = [
                $n + 1,
                $i->student->nim,
                $i->student->name,
                $i->student->studentProfile?->studyProgram?->name,
                $i->placeName(),
                $i->position,
                $i->lecturer?->name,
                $i->periodLabel(),
                $i->statusLabel(),
                $i->resultLabel(),
                self::finalScore($i),
                self::finalGrade($i),
                $i->defenseScore ? (float) $i->defenseScore->score : null,
                $i->defenseScore?->letter_grade,
                $i->defenseScore?->defended_at?->format('d-m-Y'),
                self::reportProgress($i),
                self::recruitmentLabel($i),
            ];
        }

        $title = 'Rekap KP '.Campus::get('campus.program').' · '.$filterLabel.' · diunduh '.now()->locale('id')->translatedFormat('j F Y');

        return (new SimpleXlsx)
            ->addSheet('Rekap KP', $rows, [5, 12, 30, 32, 26, 28, 26, 12, 14, 11, 7, 12, 8, 13, 12, 34], $title)
            ->save();
    }
}

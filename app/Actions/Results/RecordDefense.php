<?php

namespace App\Actions\Results;

use App\Models\ActivityLog;
use App\Models\DefenseScore;
use App\Models\Internship;
use App\Models\User;
use App\Support\Notify;
use App\Support\Num;
use Illuminate\Validation\ValidationException;

/**
 * Nilai sidang KP = nilai laporan akhir dari dosen pembimbing (yang juga penguji) 50%
 * + nilai industri dari penilaian Company Supervisor yang sudah divalidasi 50%.
 * Kaprodi memeriksa lalu mengesahkan; nilai akhir penilaian Company Supervisor tidak berubah.
 */
class RecordDefense
{
    public const LECTURER_WEIGHT = 0.5;

    public const INDUSTRY_WEIGHT = 0.5;

    /** KP yang boleh disahkan nilai sidangnya: sudah selesai, atau masa magangnya sudah berakhir. */
    public static function eligible(Internship $internship): bool
    {
        return $internship->status === 'selesai'
            || ($internship->status === 'berjalan' && $internship->end_date->lte(today()));
    }

    /**
     * Komponen nilai sidang yang tersedia saat ini.
     *
     * @return array{lecturer: ?float, industry: ?float, score: ?float}
     */
    public static function components(Internship $internship): array
    {
        $lecturer = $internship->reportScore ? (float) $internship->reportScore->total : null;
        $industry = $internship->assessment?->isValidated() ? (float) $internship->assessment->final_score : null;

        return [
            'lecturer' => $lecturer,
            'industry' => $industry,
            'score' => $lecturer !== null && $industry !== null
                ? round($lecturer * self::LECTURER_WEIGHT + $industry * self::INDUSTRY_WEIGHT, 2)
                : null,
        ];
    }

    /** @param  array{defended_at: string, note?: ?string}  $data */
    public function handle(User $kaprodi, Internship $internship, array $data): DefenseScore
    {
        if (! self::eligible($internship)) {
            throw ValidationException::withMessages(['defended_at' => 'Nilai sidang baru bisa disahkan setelah masa magang selesai.']);
        }
        $parts = self::components($internship);
        if ($parts['lecturer'] === null) {
            throw ValidationException::withMessages(['defended_at' => 'Dosen pembimbing belum menilai laporan akhir.']);
        }
        if ($parts['industry'] === null) {
            throw ValidationException::withMessages(['defended_at' => 'Penilaian Company Supervisor belum divalidasi.']);
        }

        $defense = $internship->defenseScore()->firstOrNew();
        $isNew = ! $defense->exists || $defense->lecturer_score === null;

        // Nilai berubah: huruf mutu dihitung ulang dengan skala yang berlaku sekarang.
        if (! $defense->exists || (float) $defense->score !== $parts['score']) {
            $defense->letter_grade = null;
        }
        $defense->fill([
            'score' => $parts['score'],
            'lecturer_score' => $parts['lecturer'],
            'industry_score' => $parts['industry'],
            'defended_at' => $data['defended_at'],
            'note' => filled($data['note'] ?? null) ? $data['note'] : null,
            'entered_by' => $kaprodi->id,
        ])->save();

        $name = $internship->student->name;
        ActivityLog::record($isNew ? 'sidang.sahkan' : 'sidang.ubah', ($isNew ? 'Mengesahkan' : 'Mengubah').' nilai sidang '.$name, $defense);
        if ($isNew) {
            Notify::send($internship->student, 'Nilai sidang KP sudah disahkan', 'Nilai '.Num::short($defense->score).' ('.$defense->letter_grade.').', route('kp.status'), 'award', 'green', email: true);
            if ($internship->lecturer) {
                Notify::send($internship->lecturer, 'Nilai sidang '.$internship->student->listName().' disahkan', 'Nilai '.Num::short($defense->score).' ('.$defense->letter_grade.').', route('laporan.diskusi', $internship).'#nilai', 'award', 'green');
            }
        }

        return $defense;
    }
}

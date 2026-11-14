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
 * Nilai sidang KP diinput kaprodi setelah masa magang selesai.
 * Dicatat terpisah: tidak mengubah nilai akhir dari penilaian Company Supervisor.
 */
class RecordDefense
{
    /** KP yang boleh diberi nilai sidang: sudah selesai, atau masa magangnya sudah berakhir. */
    public static function eligible(Internship $internship): bool
    {
        return $internship->status === 'selesai'
            || ($internship->status === 'berjalan' && $internship->end_date->lte(today()));
    }

    /** @param  array{score: numeric, defended_at: string, note?: ?string}  $data */
    public function handle(User $kaprodi, Internship $internship, array $data): DefenseScore
    {
        if (! self::eligible($internship)) {
            throw ValidationException::withMessages(['score' => 'Nilai sidang baru bisa diisi setelah masa magang selesai.']);
        }

        $defense = $internship->defenseScore()->firstOrNew();
        $isNew = ! $defense->exists;
        $score = round((float) $data['score'], 2);

        // Nilai berubah: huruf mutu dihitung ulang dengan skala yang berlaku sekarang.
        if ($isNew || (float) $defense->score !== $score) {
            $defense->letter_grade = null;
        }
        $defense->fill([
            'score' => $score,
            'defended_at' => $data['defended_at'],
            'note' => filled($data['note'] ?? null) ? $data['note'] : null,
            'entered_by' => $kaprodi->id,
        ])->save();

        $name = $internship->student->name;
        ActivityLog::record($isNew ? 'sidang.isi' : 'sidang.ubah', ($isNew ? 'Mengisi' : 'Mengubah').' nilai sidang '.$name, $defense);
        if ($isNew) {
            Notify::send($internship->student, 'Nilai sidang KP sudah keluar', 'Nilai '.Num::short($score).' ('.$defense->letter_grade.').', route('kp.status'), 'award', 'green');
        }

        return $defense;
    }
}

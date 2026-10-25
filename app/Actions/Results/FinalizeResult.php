<?php

namespace App\Actions\Results;

use App\Models\ActivityLog;
use App\Models\Internship;
use App\Models\User;
use App\Support\Campus;
use App\Support\Grading;
use App\Support\Notify;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Dosen pembimbing memutuskan hasil akhir KP, lalu KP ditutup dan dikunci.
 * - KP Industri dan Seminar: Diterima / Tidak diterima.
 * - MBKM Mandiri: nilai angka dan huruf = bobot mitra x nilai Company Supervisor + bobot dosen x nilai dosen.
 * Syarat: penilaian akhir sudah divalidasi dan bimbingan terkonfirmasi sudah memenuhi minimal.
 */
class FinalizeResult
{
    /** @return list<string> syarat yang belum terpenuhi */
    public static function blockers(Internship $internship): array
    {
        $blockers = [];
        if (! $internship->assessment?->isValidated()) {
            $blockers[] = $internship->isSeminar()
                ? 'Penilaian akhir seminar belum diisi dan divalidasi.'
                : 'Penilaian akhir dari Company Supervisor belum divalidasi.';
        }
        $min = (int) Campus::rule('min_guidance');
        $count = $internship->confirmedGuidanceCount();
        if ($count < $min) {
            $blockers[] = "Bimbingan terkonfirmasi baru {$count} dari minimal {$min}.";
        }

        return $blockers;
    }

    /** Nilai MBKM dari nilai mitra (penilaian akhir supervisor) dan nilai dosen. @return array{0: float, 1: string} */
    public static function mbkmScore(float $partner, float $lecturer): array
    {
        $pw = (float) Campus::rule('mbkm_partner_weight');
        $lw = (float) Campus::rule('mbkm_lecturer_weight');
        $final = round(($partner * $pw + $lecturer * $lw) / max(1, $pw + $lw), 2);

        return [$final, Grading::letterFor($final)];
    }

    public function handle(User $lecturer, Internship $internship, array $data): Internship
    {
        if ($internship->status !== 'berjalan') {
            throw ValidationException::withMessages(['result' => 'Hasil hanya bisa diputuskan untuk KP yang sedang berjalan.']);
        }
        if ($blockers = self::blockers($internship)) {
            throw ValidationException::withMessages(['result' => implode(' ', $blockers)]);
        }

        return DB::transaction(function () use ($lecturer, $internship, $data) {
            $fields = ['result_note' => filled($data['result_note'] ?? null) ? trim($data['result_note']) : null, 'result_by' => $lecturer->id, 'result_at' => now()];

            if ($internship->isMbkm()) {
                [$final, $grade] = self::mbkmScore((float) $internship->assessment->final_score, (float) $data['lecturer_score']);
                $fields += ['lecturer_score' => $data['lecturer_score'], 'final_score' => $final, 'final_grade' => $grade, 'result' => $grade === 'E' ? 'tidak' : 'diterima'];
            } else {
                if ($data['result'] === 'tidak' && ! filled($data['result_note'] ?? null)) {
                    throw ValidationException::withMessages(['result_note' => 'Tulis alasan jika KP tidak diterima.']);
                }
                $fields['result'] = $data['result'];
            }

            $internship->update($fields + ['status' => 'selesai']);
            $label = $internship->resultLabel();

            ActivityLog::record('kp.hasil', "Memutuskan hasil KP {$internship->student->name}: {$label}", $internship);
            Notify::send($internship->student, "Hasil KP Anda: {$label}", $internship->result_note, route('kp.status'), 'award', $internship->result === 'tidak' ? 'red' : 'green');
            Notify::send($internship->supervisor, "KP {$internship->student->name} sudah selesai", null, route('pantau.show', $internship), 'award');

            return $internship;
        });
    }
}

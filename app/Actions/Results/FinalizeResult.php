<?php

namespace App\Actions\Results;

use App\Models\ActivityLog;
use App\Models\Internship;
use App\Models\User;
use App\Support\Campus;
use App\Support\Notify;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Dosen pembimbing memutuskan hasil akhir KP (Diterima / Tidak diterima), lalu KP ditutup dan dikunci.
 * Nilai angka dan huruf mutu diambil dari penilaian akhir Company Supervisor yang sudah divalidasi.
 * Syarat: penilaian akhir sudah divalidasi dan bimbingan terkonfirmasi sudah memenuhi minimal.
 */
class FinalizeResult
{
    /** @return list<string> syarat yang belum terpenuhi */
    public static function blockers(Internship $internship): array
    {
        $blockers = [];
        if (! $internship->assessment?->isValidated()) {
            $blockers[] = 'Penilaian akhir dari Company Supervisor belum divalidasi.';
        }
        $min = (int) Campus::rule('min_guidance');
        $count = $internship->confirmedGuidanceCount();
        if ($count < $min) {
            $blockers[] = "Bimbingan terkonfirmasi baru {$count} dari minimal {$min}.";
        }

        return $blockers;
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

            if ($data['result'] === 'tidak' && ! filled($data['result_note'] ?? null)) {
                throw ValidationException::withMessages(['result_note' => 'Tulis alasan jika KP tidak diterima.']);
            }
            $fields += [
                'result' => $data['result'],
                'final_score' => $internship->assessment->final_score,
                'final_grade' => $internship->assessment->letter_grade,
            ];

            $internship->update($fields + ['status' => 'selesai']);
            $label = $internship->resultLabel();

            ActivityLog::record('kp.hasil', "Memutuskan hasil KP {$internship->student->name}: {$label}", $internship);
            Notify::send($internship->student, "Hasil KP Anda: {$label}", $internship->result_note, route('kp.status'), 'award', $internship->result === 'tidak' ? 'red' : 'green');
            Notify::send($internship->supervisor, "KP {$internship->student->name} sudah selesai", null, route('pantau.show', $internship), 'award');

            return $internship;
        });
    }
}

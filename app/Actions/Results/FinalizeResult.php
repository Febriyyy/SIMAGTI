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
 * Nilai angka dan huruf mutu = nilai sidang KP yang disahkan kaprodi (dosen pembimbing 50% + industri 50%).
 * KP yang diterima langsung mendapat sertifikat ber-QR.
 * Syarat (nilai dan sertifikat tidak muncul sebelum semuanya terpenuhi): masa magang sudah berakhir,
 * laporan akhir KP sudah diunggah, penilaian akhir sudah divalidasi, bimbingan memenuhi minimal,
 * dan nilai sidang KP sudah disahkan kaprodi.
 */
class FinalizeResult
{
    /** @return list<string> syarat yang belum terpenuhi */
    public static function blockers(Internship $internship): array
    {
        $blockers = [];
        if ($internship->end_date && $internship->end_date->isAfter(today())) {
            $blockers[] = 'Masa magang belum berakhir (sampai '.$internship->end_date->locale('id')->translatedFormat('j F Y').').';
        }
        if (! $internship->finalReport?->submitted_at) {
            $blockers[] = 'Laporan akhir KP belum diunggah mahasiswa.';
        }
        if (! $internship->assessment?->isValidated()) {
            $blockers[] = 'Penilaian akhir dari Company Supervisor belum divalidasi.';
        }
        $min = (int) Campus::rule('min_guidance');
        $count = $internship->confirmedGuidanceCount();
        if ($count < $min) {
            $blockers[] = "Bimbingan terkonfirmasi baru {$count} dari minimal {$min}.";
        }
        if (! $internship->defenseScore) {
            $blockers[] = 'Mahasiswa belum sidang KP (nilai sidang belum disahkan kaprodi).';
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
                'final_score' => $internship->defenseScore->score,
                'final_grade' => $internship->defenseScore->letter_grade,
            ];

            $internship->update($fields + ['status' => 'selesai']);
            app(IssueCertificate::class)->handle($internship, $lecturer);
            $label = $internship->resultLabel();

            ActivityLog::record('kp.hasil', "Memutuskan hasil KP {$internship->student->name}: {$label}", $internship);
            Notify::send($internship->student, "Hasil KP Anda: {$label}", $internship->result_note, route('kp.status'), 'award', $internship->result === 'tidak' ? 'red' : 'green');
            Notify::send($internship->supervisor, "KP {$internship->student->name} sudah selesai", null, route('pantau.show', $internship), 'award');

            return $internship;
        });
    }
}

<?php

namespace Database\Seeders\Concerns;

use App\Actions\Documents\ManageDocuments;
use App\Models\Assessment;
use App\Models\ChangeRequest;
use App\Models\GuidanceSession;
use App\Models\Internship;
use App\Models\InternshipDocument;
use App\Models\StoredFile;
use App\Models\User;
use App\Notifications\KpNotice;
use App\Support\AssessmentStandard;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * Data contoh KP tahap 2 yang dipakai DemoSeeder (tes) dan DataSeeder (aplikasi):
 * konfirmasi supervisor, bimbingan, dokumen, hasil akhir, perubahan, notifikasi.
 * Isinya tetap (tidak acak) supaya tes bisa memakai jumlah yang sama.
 */
trait SeedsStageTwo
{
    protected function confirmPlacement(Internship $internship, ?string $disputeNote = null, int $daysAfterStart = 2): void
    {
        // Sanggahan dibuat baru-baru ini (supervisor baru diaktifkan ulang), konfirmasi di awal KP
        $at = $disputeNote
            ? now()->subDays(2)->setTime(10, 15)
            : $internship->start_date->copy()->addDays($daysAfterStart)->setTime(10, 15)->min(now());
        $internship->forceFill([
            'supervisor_confirmation' => $disputeNote ? 'disputed' : 'confirmed',
            'supervisor_confirmation_note' => $disputeNote,
            'supervisor_confirmed_at' => $at,
        ])->saveQuietly();
    }

    /** Log bimbingan: sesi terkonfirmasi tersebar dari awal KP sampai sekarang, lalu sesi yang menunggu konfirmasi. */
    protected function seedGuidance(Internship $internship, User $lecturer, int $confirmed, int $pending = 0): void
    {
        $until = Carbon::today()->min($internship->end_date);
        $span = max(7, $internship->start_date->diffInDays($until));
        $total = $confirmed + $pending;

        for ($n = 0; $n < $total; $n++) {
            $held = $internship->start_date->copy()->addDays((int) round(($n + 1) * $span / ($total + 1)));
            if ($held->isWeekend()) {
                $held->next(Carbon::MONDAY);
            }
            $held = $held->min(Carbon::today());
            $isPending = $n >= $confirmed;
            $session = GuidanceSession::create([
                'internship_id' => $internship->id,
                'held_on' => $held->toDateString(),
                'mode' => $n % 3 === 1 ? 'luring' : 'daring',
                'topic' => self::GUIDANCE_TOPICS[$n % count(self::GUIDANCE_TOPICS)],
                'notes' => self::GUIDANCE_NOTES[$n % count(self::GUIDANCE_NOTES)],
                'status' => $isPending ? 'pending' : 'confirmed',
                'decided_by' => $isPending ? null : $lecturer->id,
                'decided_at' => $isPending ? null : $held->copy()->addDay()->setTime(8, 0)->min(now()),
                'created_by' => $internship->student_id,
            ]);
            $at = $held->copy()->setTime(15, 0)->min(now());
            $session->forceFill(['created_at' => $at, 'updated_at' => $at])->saveQuietly();
        }
    }

    /**
     * Dokumen KP mandiri. Status: menunggu | diunggah | diterima | ditolak.
     */
    protected function seedDocuments(Internship $internship, ?User $tu, string $reply, bool $letter = true): void
    {
        if ($letter && $tu) {
            $issued = $internship->start_date->copy()->subDays(12)->setTime(10, 0)->min(now());
            $doc = InternshipDocument::create([
                'internship_id' => $internship->id, 'type' => InternshipDocument::LETTER, 'status' => 'terbit',
                'number' => ManageDocuments::nextLetterNumber($issued),
                'requested_by' => $internship->student_id, 'processed_by' => $tu->id, 'processed_at' => $issued,
            ]);
            $doc->forceFill(['created_at' => $internship->start_date->copy()->subDays(14)->min(now())])->saveQuietly();
        }

        ManageDocuments::ensureFor($internship);

        $status = $reply;
        $doc = $internship->documents()->where('type', InternshipDocument::REPLY)->first();
        if ($doc && $status !== 'menunggu') {
            $name = 'surat-balasan-'.$internship->student->nim.'.pdf';
            $path = 'dokumen/'.$internship->id.'/'.$name;
            $pdf = self::tinyPdf('Surat balasan perusahaan (contoh)');
            Storage::disk(ManageDocuments::DISK)->put($path, $pdf);
            $file = StoredFile::create([
                'disk' => ManageDocuments::DISK, 'path' => $path, 'original_name' => $name, 'mime_type' => 'application/pdf',
                'size' => strlen($pdf), 'uploaded_by' => $internship->student_id,
            ]);
            $uploaded = $internship->start_date->copy()->addDays(6)->setTime(13, 0)->min(now());
            $doc->forceFill([
                'status' => $status,
                'file_id' => $file->id,
                'note' => $status === 'ditolak' ? 'Tanda tangan dan cap perusahaan belum terlihat. Unggah ulang hasil pindaian yang jelas.' : null,
                'processed_by' => in_array($status, ['diterima', 'ditolak'], true) ? $tu?->id : null,
                'processed_at' => in_array($status, ['diterima', 'ditolak'], true) ? $uploaded->copy()->addDay()->min(now()) : null,
                'updated_at' => $uploaded,
            ])->saveQuietly();
        }
    }

    /** Hasil akhir KP yang sudah selesai: diterima, nilai dari penilaian akhir yang sudah divalidasi. */
    protected function seedResult(Internship $internship, User $lecturer, ?string $note = null): void
    {
        $internship->loadMissing('assessment');
        $internship->forceFill([
            'result' => 'diterima',
            'result_note' => $note,
            'result_by' => $lecturer->id,
            'result_at' => $internship->end_date->copy()->addDays(10)->setTime(10, 0)->min(now()),
            'final_score' => $internship->assessment?->final_score,
            'final_grade' => $internship->assessment?->letter_grade,
        ])->saveQuietly();
    }

    /**
     * Penilaian akhir Company Supervisor dengan standar kampus.
     *
     * @param  list<float>  $values  nilai skala per pertanyaan (berputar bila lebih sedikit dari jumlah pertanyaan)
     * @param  int|null  $answered  hanya sekian pertanyaan pertama yang dinilai (draft); null = semua
     */
    protected function seedAssessment(Internship $internship, User $supervisor, array $values, bool $submit, ?User $validator = null,
        ?string $note = null, ?string $comment = null, ?int $answered = null): Assessment
    {
        $rubric = AssessmentStandard::rubric();
        $assessment = Assessment::create([
            'internship_id' => $internship->id,
            'rubric_id' => $rubric->id,
            'assessor_id' => $supervisor->id,
            'comment' => $comment,
        ]);

        foreach ($rubric->aspects->values() as $k => $aspect) {
            if ($answered !== null && $k >= $answered) {
                break;
            }
            $assessment->scores()->create(['rubric_aspect_id' => $aspect->id, 'score' => $values[$k % count($values)]]);
        }

        if ($submit) {
            $assessment->forceFill(['category_weights' => AssessmentStandard::weights()]);
        }
        $assessment->forceFill(['final_score' => $assessment->computeFinalScore()])->save();

        if ($submit) {
            $assessment->submit();
            $at = $internship->end_date->copy()->subDays(5)->setTime(15, 0)->min(now());
            $assessment->forceFill(['submitted_at' => $at])->saveQuietly();
        }
        if ($validator) {
            $assessment->validateBy($validator, $note);
        }

        return $assessment;
    }

    protected function seedChange(Internship $internship, string $type, string $step, array $payload, string $reason, int $daysAgo = 2, ?string $supervisorNote = null): ChangeRequest
    {
        $change = ChangeRequest::create([
            'internship_id' => $internship->id, 'type' => $type, 'status' => 'pending', 'step' => $step,
            'payload' => $payload, 'reason' => $reason, 'requested_by' => $internship->student_id,
            'supervisor_note' => $supervisorNote, 'supervisor_acked_at' => $supervisorNote ? now()->subDays(max(0, $daysAgo - 1)) : null,
        ]);
        $change->forceFill(['created_at' => now()->subDays($daysAgo), 'updated_at' => now()->subDays(max(0, $daysAgo - 1))])->saveQuietly();

        return $change;
    }

    /** @param  list<array{0: string, 1: ?string, 2: string, 3: string, 4?: string}>  $items  [judul, isi, nama route, ikon, warna] */
    protected function seedNotices(User $user, array $items, int $read = 0): void
    {
        foreach ($items as $k => $item) {
            $user->notify(new KpNotice($item[0], $item[1], route($item[2]), $item[3], $item[4] ?? 'blue'));
        }
        $user->notifications()->latest()->get()->each(function ($n, $k) use ($items, $read) {
            $at = now()->subHours(($k + 1) * 7);
            $n->forceFill(['created_at' => $at, 'updated_at' => $at, 'read_at' => $k >= count($items) - $read ? $at->copy()->addHour() : null])->save();
        });
    }

    private static function tinyPdf(string $text): string
    {
        $stream = 'BT /F1 18 Tf 72 760 Td ('.$text.') Tj ET';
        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>',
            '<< /Length '.strlen($stream)." >>\nstream\n".$stream."\nendstream",
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        ];
        $pdf = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objects as $i => $obj) {
            $offsets[] = strlen($pdf);
            $pdf .= ($i + 1)." 0 obj\n".$obj."\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";
        foreach ($offsets as $o) {
            $pdf .= sprintf("%010d 00000 n \n", $o);
        }

        return $pdf.'trailer << /Size '.(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n".$xref."\n%%EOF\n";
    }

    private const GUIDANCE_TOPICS = [
        'Perkenalan dan rencana KP',
        'Pembahasan rencana kegiatan dan goals',
        'Kerangka laporan akhir',
        'Progres bab 1 dan bab 2',
        'Evaluasi tengah periode',
        'Persiapan presentasi akhir',
        'Revisi laporan akhir',
    ];

    private const GUIDANCE_NOTES = [
        'Menyepakati jadwal bimbingan dua mingguan.',
        'Goals diperjelas dan dikaitkan dengan rencana kegiatan di timesheet.',
        'Kerangka laporan disetujui, mulai menulis bab pendahuluan.',
        'Bab 2 perlu ditambah referensi pendukung.',
        'Kegiatan sesuai rencana, lanjutkan dokumentasi bukti kerja.',
        'Latihan presentasi maksimal 10 menit.',
        'Perbaikan format daftar pustaka.',
    ];
}

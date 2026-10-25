<?php

namespace Database\Seeders\Concerns;

use App\Actions\Documents\ManageDocuments;
use App\Models\AssessmentRubric;
use App\Models\ChangeRequest;
use App\Models\CreditRecognition;
use App\Models\GuidanceSession;
use App\Models\Internship;
use App\Models\InternshipDocument;
use App\Models\MonthlyReview;
use App\Models\StoredFile;
use App\Models\User;
use App\Notifications\KpNotice;
use App\Support\Grading;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * Data contoh KP tahap 2 yang dipakai DemoSeeder (tes) dan DataSeeder (aplikasi):
 * konfirmasi supervisor, evaluasi bulanan, bimbingan, dokumen, hasil akhir, perubahan, rekognisi SKS, kuesioner, notifikasi.
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

    /**
     * Evaluasi bulanan untuk bulan-bulan yang sudah berakhir.
     *
     * @param  int  $openMonths  jumlah bulan berakhir terakhir yang baru direfleksikan (belum dinilai)
     * @param  int  $unreflected  jumlah bulan berakhir terakhir yang refleksinya belum diisi (mahasiswa pasif)
     */
    protected function seedMonths(Internship $internship, User $reviewer, ?User $lecturer, int $openMonths = 1, int $unreflected = 0): void
    {
        $ended = [];
        for ($m = 1; $m <= $internship->monthCount(); $m++) {
            [, $end] = $internship->monthRange($m);
            if ($end->copy()->endOfDay()->isPast()) {
                $ended[] = $m;
            }
        }

        $goals = $internship->syllabusItems()->pluck('id')->all();
        $count = count($ended);

        foreach ($ended as $k => $m) {
            $fromEnd = $count - $k; // 1 = bulan berakhir terakhir
            if ($fromEnd <= $unreflected) {
                MonthlyReview::forMonth($internship, $m);

                continue;
            }

            $review = MonthlyReview::forMonth($internship, $m);
            $end = $review->period_end->copy();
            $i = ($internship->id + $m) % count(self::LEARNED);

            $review->forceFill([
                'learned' => self::LEARNED[$i],
                'obstacles' => self::OBSTACLES[$i],
                'plan' => self::PLANS[$i],
                'syllabus_item_ids' => $goals ? array_values(array_slice($goals, ($m - 1) % max(1, count($goals)), 2)) : null,
                'reflected_at' => $end->copy()->subDays(1)->setTime(16, 30)->min(now()),
            ]);

            if ($fromEnd > $openMonths + $unreflected) {
                $base = 2 + (($internship->id + $m) % 2); // 2 atau 3, naik di bulan-bulan akhir
                $lift = $m >= 3 ? 1 : 0;
                $review->forceFill([
                    'score_hard' => min(4, $base + $lift),
                    'score_soft' => min(4, 3 + ($m % 2 === 0 ? $lift : 0)),
                    'score_attitude' => min(4, 3 + $lift),
                    'reviewer_note' => self::REVIEWER_NOTES[$i],
                    'reviewed_by' => $reviewer->id,
                    'reviewed_at' => $end->copy()->addDays(2)->setTime(11, 0)->min(now()),
                ]);
                if ($lecturer && ! $internship->isSeminar()) {
                    $review->forceFill([
                        'lecturer_note' => self::LECTURER_NOTES[$i],
                        'lecturer_noted_by' => $lecturer->id,
                        'lecturer_noted_at' => $end->copy()->addDays(4)->setTime(9, 0)->min(now()),
                    ]);
                }
            }
            $review->save();
        }
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
    protected function seedDocuments(Internship $internship, ?User $tu, ?User $biro, string $reply, ?string $pact = null, bool $letter = true): void
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

        foreach ([InternshipDocument::REPLY => $reply, InternshipDocument::PACT => $pact] as $type => $status) {
            $doc = $internship->documents()->where('type', $type)->first();
            if (! $doc || ! $status || $status === 'menunggu') {
                continue;
            }
            $name = ($type === InternshipDocument::REPLY ? 'surat-balasan' : 'pakta-integritas').'-'.$internship->student->nim.'.pdf';
            $path = 'dokumen/'.$internship->id.'/'.$name;
            $pdf = self::tinyPdf($type === InternshipDocument::REPLY ? 'Surat balasan perusahaan (contoh)' : 'Pakta integritas MBKM (contoh)');
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
                'processed_by' => in_array($status, ['diterima', 'ditolak'], true) ? $biro?->id : null,
                'processed_at' => in_array($status, ['diterima', 'ditolak'], true) ? $uploaded->copy()->addDay()->min(now()) : null,
                'updated_at' => $uploaded,
            ])->saveQuietly();
        }
    }

    /** Hasil akhir KP yang sudah selesai (MBKM: nilai dosen digabung 60/40 dengan nilai mitra). */
    protected function seedResult(Internship $internship, User $lecturer, ?float $lecturerScore = null, ?string $note = null): void
    {
        $internship->loadMissing('assessment');
        $fields = ['result_note' => $note, 'result_by' => $lecturer->id, 'result_at' => $internship->end_date->copy()->addDays(10)->setTime(10, 0)->min(now())];

        if ($internship->isMbkm()) {
            $partner = (float) $internship->assessment?->final_score;
            $final = round(($partner * 60 + $lecturerScore * 40) / 100, 2);
            $fields += ['lecturer_score' => $lecturerScore, 'final_score' => $final, 'final_grade' => Grading::letterFor($final), 'result' => 'diterima'];
        } else {
            $fields['result'] = 'diterima';
        }

        $internship->forceFill($fields)->saveQuietly();
    }

    /** Kuesioner milik dosen pembimbing: salinan rubrik kampus ditambah dua pertanyaan isian. */
    protected function seedQuestionnaire(User $lecturer, string $name, array $assignTo = []): AssessmentRubric
    {
        $source = AssessmentRubric::active();
        $rubric = AssessmentRubric::create(['name' => $name, 'is_active' => false, 'owner_id' => $lecturer->id, 'created_by' => $lecturer->id]);
        $order = 0;
        foreach ($source->aspects()->orderBy('sort_order')->get() as $aspect) {
            $rubric->aspects()->create($aspect->only(['name', 'description', 'weight']) + ['kind' => 'score', 'sort_order' => $order++]);
        }
        $rubric->aspects()->create(['name' => 'Kelebihan utama mahasiswa', 'description' => 'Satu atau dua kalimat', 'kind' => 'text', 'weight' => 0, 'sort_order' => $order++]);
        $rubric->aspects()->create(['name' => 'Saran pengembangan', 'description' => 'Hal yang perlu ditingkatkan', 'kind' => 'text', 'weight' => 0, 'sort_order' => $order]);

        foreach ($assignTo as $internship) {
            $internship->forceFill(['assessment_rubric_id' => $rubric->id])->saveQuietly();
        }

        return $rubric;
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

    protected function seedRecognition(Internship $internship, array $courses, string $status = 'diajukan', ?User $kaprodi = null): void
    {
        CreditRecognition::create([
            'internship_id' => $internship->id,
            'courses' => array_map(fn ($c) => ['code' => $c[0], 'name' => $c[1], 'credits' => $c[2]], $courses),
            'status' => $status,
            'decided_by' => $status === 'diajukan' ? null : $kaprodi?->id,
            'decided_at' => $status === 'diajukan' ? null : now()->subDays(3),
        ]);
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

    private const LEARNED = [
        'Memahami alur kerja tim dari perencanaan sprint sampai rilis, dan mulai terbiasa membaca kode yang sudah ada sebelum mengubahnya.',
        'Belajar menulis query yang lebih efisien dan memakai indeks dengan tepat setelah menemukan halaman yang lambat.',
        'Menjadi lebih percaya diri presentasi hasil kerja saat stand-up dan menjelaskan kendala dengan singkat.',
        'Mulai menulis pengujian otomatis untuk fitur yang saya kerjakan dan memahami pentingnya code review.',
    ];

    private const OBSTACLES = [
        'Butuh waktu menyesuaikan diri dengan standar penamaan dan struktur folder proyek.',
        'Beberapa kali tertunda karena menunggu akses ke server pengujian.',
        'Masih sulit memperkirakan waktu pengerjaan task yang besar.',
        'Dokumentasi internal untuk modul lama belum lengkap.',
    ];

    private const PLANS = [
        'Mengambil satu fitur dari awal sampai akhir dan menulis dokumentasinya.',
        'Memecah task besar menjadi bagian kecil agar estimasi lebih tepat.',
        'Belajar dari review supervisor dan mengurangi revisi berulang.',
        'Mulai menyiapkan bahan laporan akhir dari catatan timesheet.',
    ];

    private const REVIEWER_NOTES = [
        'Perkembangan baik, pertahankan konsistensi.',
        'Komunikasi sudah lebih lancar. Teknis perlu lebih teliti.',
        'Inisiatif bagus, bulan depan coba pegang fitur sendiri.',
        null,
    ];

    private const LECTURER_NOTES = [
        'Refleksi sudah jelas. Kaitkan dengan bab metodologi laporan.',
        'Catat kendala teknis dengan rinci, berguna untuk pembahasan laporan.',
        'Progres sesuai rencana silabus. Lanjutkan.',
        'Perbanyak bukti kerja di task agar mudah disusun di laporan.',
    ];

    private const GUIDANCE_TOPICS = [
        'Perkenalan dan rencana KP',
        'Pembahasan silabus dan target bulanan',
        'Kerangka laporan akhir',
        'Progres bab 1 dan bab 2',
        'Evaluasi tengah periode',
        'Persiapan presentasi akhir',
        'Revisi laporan akhir',
    ];

    private const GUIDANCE_NOTES = [
        'Menyepakati jadwal bimbingan dua mingguan.',
        'Tujuan hard skill diperjelas dan dikaitkan dengan proyek yang dikerjakan.',
        'Kerangka laporan disetujui, mulai menulis bab pendahuluan.',
        'Bab 2 perlu ditambah referensi pendukung.',
        'Kegiatan sesuai rencana, lanjutkan dokumentasi bukti kerja.',
        'Latihan presentasi maksimal 10 menit.',
        'Perbaikan format daftar pustaka.',
    ];
}

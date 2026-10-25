<?php

namespace App\Actions\Monthly;

use App\Models\ActivityLog;
use App\Models\Internship;
use App\Models\MonthlyReview;
use App\Models\User;
use App\Support\Notify;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Siklus bulanan: mahasiswa mengisi refleksi -> peninjau (Company Supervisor; seminar: dosen pembimbing)
 * memberi nilai 1-4 untuk hard skill, soft skill, attitude -> dosen pembimbing memberi catatan (magang).
 * Tidak ada langkah yang menghentikan langkah lain: peninjau boleh menilai bulan yang sudah berakhir
 * walau refleksi belum diisi.
 */
class MonthlyCycle
{
    public function reflect(User $student, Internship $internship, int $month, array $data): MonthlyReview
    {
        $this->checkMonth($internship, $month);
        $review = MonthlyReview::forMonth($internship, $month);

        if ($review->isReviewed()) {
            throw ValidationException::withMessages(['learned' => 'Bulan ini sudah dinilai, refleksi tidak bisa diubah lagi.']);
        }
        if (! $review->isOpenForReflection()) {
            throw ValidationException::withMessages(['learned' => 'Refleksi bisa diisi mulai seminggu sebelum bulan KP berakhir.']);
        }

        $validIds = $internship->syllabusItems()->pluck('id')->all();
        $first = ! $review->isReflected();

        $review->update([
            'learned' => trim($data['learned']),
            'obstacles' => filled($data['obstacles'] ?? null) ? trim($data['obstacles']) : null,
            'plan' => filled($data['plan'] ?? null) ? trim($data['plan']) : null,
            'syllabus_item_ids' => array_values(array_intersect(array_map('intval', $data['syllabus_item_ids'] ?? []), $validIds)),
            'reflected_at' => now(),
        ]);

        ActivityLog::record('bulanan.refleksi', "Mengisi refleksi bulan ke-{$month}", $review);
        if ($first) {
            $reviewer = $internship->isSeminar() ? $internship->lecturer : $internship->supervisor;
            Notify::send($reviewer, "Refleksi bulan ke-{$month} {$student->name} sudah masuk", 'Silakan beri nilai bulanan.', route('pantau.show', [$internship, 'tab' => 'bulanan']), 'history');
        }

        return $review;
    }

    public function review(User $reviewer, Internship $internship, int $month, array $data): MonthlyReview
    {
        $this->checkMonth($internship, $month);
        $review = MonthlyReview::forMonth($internship, $month);

        if (! $review->isOpenForReview()) {
            throw ValidationException::withMessages(['score_hard' => 'Bulan ini belum berakhir dan refleksi belum diisi.']);
        }
        if ($review->lecturer_noted_at !== null) {
            throw ValidationException::withMessages(['score_hard' => 'Dosen pembimbing sudah memberi catatan, nilai bulan ini terkunci.']);
        }

        return DB::transaction(function () use ($reviewer, $internship, $month, $data, $review) {
            $first = ! $review->isReviewed();
            $review->update([
                'score_hard' => (int) $data['score_hard'],
                'score_soft' => (int) $data['score_soft'],
                'score_attitude' => (int) $data['score_attitude'],
                'reviewer_note' => filled($data['reviewer_note'] ?? null) ? trim($data['reviewer_note']) : null,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
            ]);

            ActivityLog::record('bulanan.nilai', "Menilai bulan ke-{$month} {$internship->student->name}", $review);
            if ($first) {
                Notify::send($internship->student, "Bulan ke-{$month} sudah dinilai", null, route('bulanan.index'), 'award', 'green');
                if (! $internship->isSeminar()) {
                    Notify::send($internship->lecturer, "Bulan ke-{$month} {$internship->student->name} sudah dinilai supervisor", 'Silakan beri catatan.', route('pantau.show', [$internship, 'tab' => 'bulanan']), 'chat');
                }
            }

            return $review;
        });
    }

    public function lecturerNote(User $lecturer, Internship $internship, int $month, string $note): MonthlyReview
    {
        $review = MonthlyReview::where('internship_id', $internship->id)->where('month_number', $month)->first();

        if (! $review?->isReviewed()) {
            throw ValidationException::withMessages(['lecturer_note' => 'Catatan diberikan setelah supervisor menilai bulan ini.']);
        }

        $review->update(['lecturer_note' => trim($note), 'lecturer_noted_by' => $lecturer->id, 'lecturer_noted_at' => now()]);
        ActivityLog::record('bulanan.catatan', "Memberi catatan bulan ke-{$month} {$internship->student->name}", $review);
        Notify::send($internship->student, "Catatan dosen untuk bulan ke-{$month}", null, route('bulanan.index'), 'chat');

        return $review;
    }

    private function checkMonth(Internship $internship, int $month): void
    {
        if ($month < 1 || $month > $internship->monthCount()) {
            abort(404);
        }
        [$start] = $internship->monthRange($month);
        if ($start->isFuture()) {
            throw ValidationException::withMessages(['learned' => 'Bulan ini belum dimulai.']);
        }
    }
}

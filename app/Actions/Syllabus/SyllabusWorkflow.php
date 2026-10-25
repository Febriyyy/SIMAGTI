<?php

namespace App\Actions\Syllabus;

use App\Models\ActivityLog;
use App\Models\Internship;
use App\Models\SyllabusItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Alur silabus: mahasiswa menyusun → kirim → Company Supervisor (seminar: dosen pembimbing) menyetujui atau meminta revisi.
 * Silabus yang disetujui menjadi dasar penilaian hard skill, soft skill, dan attitude.
 */
class SyllabusWorkflow
{
    public function submit(User $student, Internship $internship): void
    {
        $missing = collect(array_keys(SyllabusItem::CATEGORIES))
            ->reject(fn ($category) => $internship->syllabusItems()->where('category', $category)->exists())
            ->map(fn ($category) => SyllabusItem::CATEGORIES[$category][0]);

        if ($missing->isNotEmpty()) {
            throw ValidationException::withMessages([
                'silabus' => 'Isi minimal satu tujuan untuk: '.$missing->implode(', ').'.',
            ]);
        }

        DB::transaction(function () use ($student, $internship) {
            $internship->update(['syllabus_status' => 'submitted', 'syllabus_note' => null]);
            ActivityLog::record('silabus.kirim', "{$student->name} mengirim silabus untuk disetujui", $internship);
        });
    }

    /** Mahasiswa menarik silabus (menunggu atau sudah disetujui) untuk diubah; perlu disetujui ulang. */
    public function reopen(User $student, Internship $internship): void
    {
        if (! in_array($internship->syllabus_status, ['submitted', 'approved'], true)) {
            throw ValidationException::withMessages(['silabus' => 'Silabus masih bisa diubah langsung.']);
        }

        DB::transaction(function () use ($student, $internship) {
            $internship->update(['syllabus_status' => 'draft']);
            ActivityLog::record('silabus.ubah', "{$student->name} membuka kembali silabus untuk diubah", $internship);
        });
    }

    public function review(User $reviewer, Internship $internship, bool $approve, ?string $note): void
    {
        if (! $approve && ! filled($note)) {
            throw ValidationException::withMessages(['catatan' => 'Tulis catatan revisi untuk mahasiswa.']);
        }

        DB::transaction(function () use ($reviewer, $internship, $approve, $note) {
            $internship->update([
                'syllabus_status' => $approve ? 'approved' : 'revision',
                'syllabus_note' => $note,
                'syllabus_reviewed_by' => $reviewer->id,
                'syllabus_reviewed_at' => now(),
            ]);

            ActivityLog::record(
                $approve ? 'silabus.setujui' : 'silabus.revisi',
                ($approve ? 'Menyetujui' : 'Meminta revisi').' silabus '.$internship->student->name,
                $internship
            );
        });
    }
}

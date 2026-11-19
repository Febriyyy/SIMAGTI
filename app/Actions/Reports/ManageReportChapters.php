<?php

namespace App\Actions\Reports;

use App\Models\ActivityLog;
use App\Models\Internship;
use App\Models\ReportChapter;
use App\Models\StoredFile;
use App\Models\User;
use App\Support\Notify;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Laporan KP per bab. Mahasiswa mengunggah tiap bab; bab berisi informasi perusahaan ditinjau Company Supervisor
 * lebih dulu (bisa berisi data sensitif), lalu semua bab ditinjau dosen pembimbing. Revisi kembali ke awal alur.
 * Setiap unggahan disimpan sebagai versi (report_chapter_versions) supaya revisi bisa dibandingkan.
 */
class ManageReportChapters
{
    public const DISK = 'local';

    public const MAX_KB = 10240;

    /** Siapkan bab bawaan untuk KP yang berjalan atau selesai. */
    public static function ensureFor(Internship $internship): Collection
    {
        if (! in_array($internship->status, ['berjalan', 'selesai'], true)) {
            return collect();
        }
        if (! $internship->reportChapters()->exists()) {
            foreach (ReportChapter::DEFAULTS as $position => [$title, $needsSupervisor]) {
                $internship->reportChapters()->firstOrCreate(['position' => $position], ['title' => $title, 'needs_supervisor' => $needsSupervisor]);
            }
        }

        return $internship->reportChapters()->with('file')->get();
    }

    public function submit(User $student, ReportChapter $chapter, UploadedFile $file): ReportChapter
    {
        $internship = $chapter->internship;
        abort_unless((int) $internship->student_id === (int) $student->id, 403);
        if ($chapter->status === 'disetujui') {
            throw ValidationException::withMessages(['berkas' => 'Bab ini sudah disetujui.']);
        }
        if (! in_array($internship->status, ['berjalan', 'selesai'], true)) {
            throw ValidationException::withMessages(['berkas' => 'Laporan hanya untuk KP yang berjalan atau selesai.']);
        }

        $path = $file->storeAs("laporan/{$internship->id}", Str::uuid().'.'.strtolower($file->extension()), self::DISK);

        return DB::transaction(function () use ($student, $chapter, $internship, $file, $path) {
            $stored = StoredFile::create([
                'disk' => self::DISK,
                'path' => $path,
                'original_name' => mb_substr($file->getClientOriginalName(), 0, 200),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'checksum' => hash_file('sha256', $file->getRealPath()),
                'uploaded_by' => $student->id,
            ]);

            $chapter->update([
                'file_id' => $stored->id,
                'version' => $chapter->version + 1,
                'submitted_at' => now(),
                'status' => $chapter->needs_supervisor ? 'menunggu_supervisor' : 'menunggu_dosen',
                // Isi berubah: persetujuan perusahaan sebelumnya tidak berlaku lagi
                'supervisor_approved_by' => null,
                'supervisor_approved_at' => null,
                'revision_note' => null,
                'revision_by' => null,
                'revision_at' => null,
            ]);
            // Versi lama tetap disimpan agar revisi bisa dibandingkan
            $chapter->versions()->create(['file_id' => $stored->id, 'version' => $chapter->version, 'submitted_at' => $chapter->submitted_at]);

            ActivityLog::record('laporan.unggah', 'Mengunggah laporan '.$chapter->label(), $chapter);
            $reviewer = $chapter->needs_supervisor ? 'supervisor' : 'lecturer';
            Notify::send(Notify::parties($internship, [$reviewer]), $internship->student->listName().' mengunggah '.$chapter->label(), 'Perlu ditinjau.', route('laporan.antrean'), 'file', 'blue');

            return $chapter;
        });
    }

    /** Company Supervisor (tahap perusahaan) atau dosen pembimbing (tahap dosen) menyetujui atau meminta revisi. */
    public function review(User $reviewer, ReportChapter $chapter, bool $approve, ?string $note): ReportChapter
    {
        $internship = $chapter->internship;
        $stage = match (true) {
            $chapter->status === 'menunggu_supervisor' && $reviewer->role === 'supervisor' && $internship->isSupervisedBy($reviewer) => 'supervisor',
            $chapter->status === 'menunggu_dosen' && $reviewer->role === 'dospem' && (int) $internship->lecturer_id === (int) $reviewer->id => 'dosen',
            default => null,
        };
        if ($stage === null) {
            throw ValidationException::withMessages(['laporan' => 'Bab ini tidak sedang menunggu tinjauan Anda.']);
        }
        if (! $approve && ! filled($note)) {
            throw ValidationException::withMessages(['catatan' => 'Tulis catatan revisi agar mahasiswa tahu yang perlu diperbaiki.']);
        }

        $student = $internship->student;
        if (! $approve) {
            $chapter->update(['status' => 'revisi', 'revision_note' => $note, 'revision_by' => $reviewer->id, 'revision_at' => now()]);
            ActivityLog::record('laporan.revisi', 'Meminta revisi '.$chapter->label().' '.$student->name, $chapter);
            Notify::send($student, $chapter->label().' perlu revisi', $note, route('laporan.diskusi', $internship).'#bab-'.$chapter->position, 'file', 'amber', email: true);

            return $chapter;
        }

        if ($stage === 'supervisor') {
            $chapter->update(['status' => 'menunggu_dosen', 'supervisor_approved_by' => $reviewer->id, 'supervisor_approved_at' => now()]);
            ActivityLog::record('laporan.setuju_perusahaan', 'Menyetujui isi '.$chapter->label().' '.$student->name, $chapter);
            Notify::send(Notify::parties($internship, ['lecturer']), $student->listName().': '.$chapter->label().' disetujui perusahaan', 'Perlu ditinjau.', route('laporan.antrean'), 'file', 'blue');
            Notify::send($student, $chapter->label().' disetujui Company Supervisor', 'Diteruskan ke dosen pembimbing.', route('dokumen.index').'#laporan', 'file', 'green');
        } else {
            $chapter->update(['status' => 'disetujui', 'approved_by' => $reviewer->id, 'approved_at' => now()]);
            ActivityLog::record('laporan.setuju', 'Menyetujui '.$chapter->label().' '.$student->name, $chapter);
            Notify::send($student, $chapter->label().' disetujui dosen pembimbing', null, route('dokumen.index').'#laporan', 'file', 'green');
        }

        return $chapter;
    }
}

<?php

namespace App\Actions\Reports;

use App\Models\ActivityLog;
use App\Models\FinalReport;
use App\Models\Internship;
use App\Models\ReportChapter;
use App\Models\ReportComment;
use App\Models\ReportScore;
use App\Models\StoredFile;
use App\Models\User;
use App\Support\Notify;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Diskusi laporan KP antara mahasiswa dan dosen pembimbing: komentar per bab dan laporan akhir,
 * berkas koreksi dari dosen, unggah laporan akhir utuh, dan nilai laporan akhir (4 aspek).
 */
class DiscussReport
{
    /** Mahasiswa pemilik KP atau dosen pembimbingnya. */
    public static function canDiscuss(User $user, Internship $internship): bool
    {
        return (int) $internship->student_id === (int) $user->id
            || ($user->role === 'dospem' && (int) $internship->lecturer_id === (int) $user->id);
    }

    /** Dosen hanya ikut berdiskusi pada bab yang boleh ia baca (bab berisi data perusahaan menunggu persetujuan perusahaan). */
    public static function canDiscussChapter(User $user, ReportChapter $chapter): bool
    {
        if (! self::canDiscuss($user, $chapter->internship)) {
            return false;
        }

        return $user->role === 'mahasiswa' || $chapter->canBeReadBy($user);
    }

    public function comment(User $user, Internship $internship, ?ReportChapter $chapter, ?string $body, ?UploadedFile $file): ReportComment
    {
        abort_unless(self::canDiscuss($user, $internship), 403);
        if ($chapter && ((int) $chapter->internship_id !== (int) $internship->id || ! self::canDiscussChapter($user, $chapter->setRelation('internship', $internship)))) {
            abort(403);
        }
        $body = filled($body) ? trim($body) : null;
        if ($body === null && ! $file) {
            throw ValidationException::withMessages(['isi' => 'Tulis komentar atau lampirkan berkas.']);
        }

        return DB::transaction(function () use ($user, $internship, $chapter, $body, $file) {
            $stored = $file ? $this->store($user, $internship, $file, 'komentar') : null;
            $comment = ReportComment::create([
                'internship_id' => $internship->id,
                'report_chapter_id' => $chapter?->id,
                'user_id' => $user->id,
                'body' => $body,
                'file_id' => $stored?->id,
            ]);

            $where = $chapter ? $chapter->label() : 'laporan akhir';
            ActivityLog::record('laporan.komentar', 'Mengomentari '.$where.' '.$internship->student->name, $comment);

            $isStudent = (int) $user->id === (int) $internship->student_id;
            $to = $isStudent ? $internship->lecturer : $internship->student;
            $title = $isStudent
                ? $internship->student->listName().' membalas diskusi '.$where
                : ($stored ? 'Berkas koreksi '.$where.' dari dosen pembimbing' : 'Komentar baru pada '.$where);
            Notify::send($to, $title, $body ? Str::limit($body, 120) : $stored?->original_name, $this->link($internship, $chapter), 'chat', $stored && ! $isStudent ? 'amber' : 'blue');

            return $comment;
        });
    }

    public function submitFinal(User $student, Internship $internship, UploadedFile $file): FinalReport
    {
        abort_unless((int) $internship->student_id === (int) $student->id, 403);
        if (! in_array($internship->status, ['berjalan', 'selesai'], true)) {
            throw ValidationException::withMessages(['berkas' => 'Laporan akhir hanya untuk KP yang berjalan atau selesai.']);
        }
        if ($internship->defenseScore?->lecturer_score !== null) {
            throw ValidationException::withMessages(['berkas' => 'Nilai sidang sudah disahkan; laporan akhir tidak bisa diganti.']);
        }

        return DB::transaction(function () use ($student, $internship, $file) {
            $stored = $this->store($student, $internship, $file, 'akhir');
            $report = $internship->finalReport()->firstOrNew();
            $old = $report->file;
            $report->fill(['file_id' => $stored->id, 'version' => $report->version + 1, 'submitted_at' => now()])->save();
            if ($old) {
                Storage::disk($old->disk)->delete($old->path);
                $old->forceDelete();
            }

            ActivityLog::record('laporan.akhir', 'Mengunggah laporan akhir', $report);
            Notify::send($internship->lecturer, $internship->student->listName().' mengunggah laporan akhir', 'Versi '.$report->version.'. Perlu ditinjau dan dinilai.', $this->link($internship, null), 'file', 'blue');

            return $report;
        });
    }

    /** @param  array<string, int|string|null>  $data  kolom aspek ReportScore::ASPECTS + note */
    public function grade(User $lecturer, Internship $internship, array $data): ReportScore
    {
        abort_unless($lecturer->role === 'dospem' && (int) $internship->lecturer_id === (int) $lecturer->id, 403);
        if (! $internship->finalReport?->file_id) {
            throw ValidationException::withMessages(['writing' => 'Nilai bisa diisi setelah mahasiswa mengunggah laporan akhir.']);
        }
        if ($internship->defenseScore?->lecturer_score !== null) {
            throw ValidationException::withMessages(['writing' => 'Nilai sidang sudah disahkan kaprodi; nilai laporan terkunci.']);
        }

        $points = collect(ReportScore::ASPECTS)->mapWithKeys(fn ($a, $key) => [$key => (int) $data[$key]]);
        $score = $internship->reportScore()->firstOrNew();
        $isNew = ! $score->exists;
        $score->fill($points->all() + [
            'total' => $points->sum(),
            'note' => filled($data['note'] ?? null) ? trim($data['note']) : null,
            'graded_by' => $lecturer->id,
            'graded_at' => now(),
        ])->save();

        ActivityLog::record($isNew ? 'laporan.nilai' : 'laporan.nilai_ubah', ($isNew ? 'Menilai' : 'Mengubah nilai').' laporan akhir '.$internship->student->name, $score);
        if ($isNew) {
            Notify::send($internship->student, 'Laporan akhir sudah dinilai dosen pembimbing', 'Nilai menunggu pengesahan kaprodi saat sidang.', $this->link($internship, null), 'award', 'green');
        }

        return $score;
    }

    private function link(Internship $internship, ?ReportChapter $chapter): string
    {
        return route('laporan.diskusi', $internship).($chapter ? '#bab-'.$chapter->position : '#akhir');
    }

    private function store(User $user, Internship $internship, UploadedFile $file, string $folder): StoredFile
    {
        $path = $file->storeAs("laporan/{$internship->id}/{$folder}", Str::uuid().'.'.strtolower($file->extension()), ManageReportChapters::DISK);

        return StoredFile::create([
            'disk' => ManageReportChapters::DISK,
            'path' => $path,
            'original_name' => mb_substr($file->getClientOriginalName(), 0, 200),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'checksum' => hash_file('sha256', $file->getRealPath()),
            'uploaded_by' => $user->id,
        ]);
    }
}

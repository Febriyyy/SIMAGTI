<?php

namespace App\Actions\Documents;

use App\Models\ActivityLog;
use App\Models\Internship;
use App\Models\InternshipDocument;
use App\Models\StoredFile;
use App\Models\User;
use App\Support\Campus;
use App\Support\Notify;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Dokumen KP. Tidak ada dokumen yang menghentikan alur:
 * surat balasan perusahaan boleh menyusul sampai batas waktu,
 * keterlambatan hanya ditandai (pengingat ke mahasiswa, daftar di TU).
 */
class ManageDocuments
{
    public const DISK = 'local';

    public const MAX_KB = 5120;

    /** Saat KP mulai berjalan: siapkan dokumen yang wajib diunggah beserta batas waktunya. */
    public static function ensureFor(Internship $internship): void
    {
        $due = $internship->start_date->copy()->addDays((int) Campus::rule('reply_letter_days'))->toDateString();

        $doc = InternshipDocument::firstOrCreate(
            ['internship_id' => $internship->id, 'type' => InternshipDocument::REPLY],
            ['status' => 'menunggu', 'due_on' => $due]
        );
        if ($doc->due_on === null) {
            $doc->update(['due_on' => $due]);
        }
    }

    /** Mahasiswa meminta surat pengantar ke TU Fakultas. */
    public function requestLetter(User $student, Internship $internship): InternshipDocument
    {
        if (! in_array($internship->status, ['diajukan', 'berjalan'], true)) {
            throw ValidationException::withMessages(['dokumen' => 'Surat pengantar hanya untuk KP yang sedang diajukan atau berjalan.']);
        }

        $existing = $internship->document(InternshipDocument::LETTER);
        if ($existing) {
            throw ValidationException::withMessages(['dokumen' => 'Surat pengantar sudah '.($existing->status === 'terbit' ? 'terbit.' : 'diminta.')]);
        }

        $doc = InternshipDocument::create([
            'internship_id' => $internship->id,
            'type' => InternshipDocument::LETTER,
            'status' => 'diminta',
            'requested_by' => $student->id,
        ]);

        ActivityLog::record('dokumen.minta', 'Meminta surat pengantar KP', $doc);

        return $doc;
    }

    /** TU Fakultas menerbitkan surat pengantar: nomor otomatis, siap dicetak dari aplikasi. */
    public function issueLetter(User $actor, InternshipDocument $doc): InternshipDocument
    {
        if ($doc->type !== InternshipDocument::LETTER || $doc->status !== 'diminta') {
            throw ValidationException::withMessages(['dokumen' => 'Surat ini sudah diproses.']);
        }

        return DB::transaction(function () use ($actor, $doc) {
            $now = now();
            $number = self::nextLetterNumber($now);

            $doc->update(['status' => 'terbit', 'number' => $number, 'processed_by' => $actor->id, 'processed_at' => $now]);
            ActivityLog::record('dokumen.terbit', "Menerbitkan surat pengantar {$number} untuk {$doc->internship->student->name}", $doc);
            Notify::send($doc->internship->student, 'Surat pengantar KP sudah terbit', "Nomor {$number}. Unduh dan cetak dari halaman Dokumen.", route('dokumen.index'), 'mail', 'green');

            return $doc;
        });
    }

    /** Nomor surat berikutnya: urut per tahun, format 001/KODE/bulan romawi/tahun. */
    public static function nextLetterNumber(\DateTimeInterface $at): string
    {
        $year = (int) $at->format('Y');
        $last = InternshipDocument::where('type', InternshipDocument::LETTER)->whereNotNull('number')
            ->where('number', 'like', '%/'.$year)->lockForUpdate()->pluck('number')
            ->map(fn (string $n) => (int) strtok($n, '/'))->max() ?? 0;
        $roman = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'][(int) $at->format('n') - 1];

        return sprintf('%03d/%s/%s/%d', $last + 1, Campus::get('campus.letter_code'), $roman, $year);
    }

    /** Mahasiswa mengunggah surat balasan perusahaan (PDF/JPG/PNG, disimpan privat). */
    public function upload(User $student, Internship $internship, string $type, UploadedFile $file): InternshipDocument
    {
        if ($type !== InternshipDocument::REPLY) {
            throw ValidationException::withMessages(['berkas' => 'Jenis dokumen tidak dikenal.']);
        }

        $doc = $internship->document($type);
        if ($doc?->status === 'diterima') {
            throw ValidationException::withMessages(['berkas' => 'Dokumen ini sudah diterima.']);
        }

        $path = $file->storeAs("dokumen/{$internship->id}", Str::uuid().'.'.strtolower($file->extension()), self::DISK);

        return DB::transaction(function () use ($student, $internship, $type, $file, $path, $doc) {
            $stored = StoredFile::create([
                'disk' => self::DISK,
                'path' => $path,
                'original_name' => mb_substr($file->getClientOriginalName(), 0, 200),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'checksum' => hash_file('sha256', $file->getRealPath()),
                'uploaded_by' => $student->id,
            ]);

            $old = $doc?->file;
            $doc = InternshipDocument::updateOrCreate(
                ['internship_id' => $internship->id, 'type' => $type],
                ['status' => 'diunggah', 'file_id' => $stored->id, 'note' => null]
            );
            if ($old) {
                Storage::disk($old->disk)->delete($old->path);
                $old->forceDelete();
            }

            ActivityLog::record('dokumen.unggah', 'Mengunggah '.mb_strtolower($doc->label()), $doc);
            Notify::send(Notify::role('tu'), mb_strtolower($doc->label()).' '.$internship->student->name.' perlu diperiksa', null, route('tu.dokumen'), 'file');

            return $doc;
        });
    }

    /** TU Fakultas memeriksa unggahan: diterima, atau diminta unggah ulang dengan catatan. */
    public function review(User $actor, InternshipDocument $doc, bool $accept, ?string $note): InternshipDocument
    {
        if ($doc->status !== 'diunggah') {
            throw ValidationException::withMessages(['dokumen' => 'Dokumen ini tidak sedang menunggu diperiksa.']);
        }
        if (! $accept && ! filled($note)) {
            throw ValidationException::withMessages(['catatan' => 'Tulis alasan agar mahasiswa tahu yang perlu diperbaiki.']);
        }

        $doc->update([
            'status' => $accept ? 'diterima' : 'ditolak',
            'note' => $note,
            'processed_by' => $actor->id,
            'processed_at' => now(),
            // Diminta unggah ulang: beri waktu 7 hari lagi bila batas lama sudah lewat
            'due_on' => ! $accept && ($doc->due_on === null || $doc->due_on->isPast()) ? Carbon::today()->addDays(7) : $doc->due_on,
        ]);

        ActivityLog::record($accept ? 'dokumen.terima' : 'dokumen.tolak', ($accept ? 'Menerima ' : 'Menolak ').mb_strtolower($doc->label()).' '.$doc->internship->student->name, $doc);
        Notify::send($doc->internship->student, $doc->label().($accept ? ' diterima' : ' perlu diunggah ulang'), $note, route('dokumen.index'), 'file', $accept ? 'green' : 'red');

        return $doc;
    }
}

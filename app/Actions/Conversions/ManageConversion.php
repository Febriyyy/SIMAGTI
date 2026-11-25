<?php

namespace App\Actions\Conversions;

use App\Models\ActivityLog;
use App\Models\ConversionCandidate;
use App\Models\Internship;
use App\Models\StoredFile;
use App\Models\User;
use App\Support\Notify;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Rekomendasi jadi karyawan dan tawaran kerja resmi.
 *
 * Alur: Company Supervisor merekomendasikan, lalu mengirim tawaran kerja lengkap (detail pekerjaan + surat PDF dengan template perusahaan, opsional
 * + batas waktu jawaban). Mahasiswa baru diberi tahu saat ditawari, membaca detail dan suratnya, lalu menerima
 * atau menolak dengan alasan. Lewat batas waktu tanpa jawaban: kedaluwarsa (bukan dianggap menolak);
 * supervisor bisa memperpanjang batas waktu.
 */
class ManageConversion
{
    public const DISK = 'local';

    public const LETTER_MAX_KB = 5120;

    public function recommend(User $supervisor, Internship $internship, ?string $position, ?string $note): ConversionCandidate
    {
        $this->guardSupervisor($supervisor, $internship);
        if ($internship->conversion) {
            throw ValidationException::withMessages(['rekrutmen' => 'Mahasiswa ini sudah direkomendasikan.']);
        }

        return DB::transaction(function () use ($supervisor, $internship, $position, $note) {
            $candidate = ConversionCandidate::create([
                'internship_id' => $internship->id,
                'status' => 'direkomendasikan',
                'offered_position' => $position ?: null,
                'recommended_by' => $supervisor->id,
                'note' => $note ?: null,
            ]);
            $candidate->histories()->create(['to_status' => 'direkomendasikan', 'changed_by' => $supervisor->id, 'note' => $note ?: null]);

            ActivityLog::record('rekrutmen.rekomendasi', 'Merekomendasikan '.$internship->student->name.' untuk direkrut', $candidate);
            Notify::send(Notify::parties($internship, ['lecturer']), $internship->student->listName().' direkomendasikan untuk direkrut', $internship->placeName(), route('pantau.show', $internship), 'star', 'green');

            return $candidate;
        });
    }

    /**
     * Kirim tawaran kerja resmi.
     *
     * @param  array{posisi: string, unit?: ?string, lokasi: string, mode: string, jenis: string, durasi?: ?int,
     *               mulai?: ?string, deskripsi: string, kompensasi?: ?string, hr_nama: string, hr_email: string, batas: string}  $data
     */
    public function offer(User $supervisor, Internship $internship, array $data, ?UploadedFile $letter = null): ConversionCandidate
    {
        $this->guardSupervisor($supervisor, $internship);
        $candidate = $this->candidateIn($internship, ['direkomendasikan']);

        // Surat dengan template perusahaan sendiri (opsional); tanpa unggahan dipakai surat resmi SIMAGTI.
        $path = $letter?->storeAs("rekrutmen/{$internship->id}", Str::uuid().'.pdf', self::DISK);

        return DB::transaction(function () use ($supervisor, $internship, $data, $letter, $path, $candidate) {
            $stored = $letter ? StoredFile::create([
                'disk' => self::DISK,
                'path' => $path,
                'original_name' => mb_substr($letter->getClientOriginalName(), 0, 200),
                'mime_type' => 'application/pdf',
                'size' => $letter->getSize(),
                'checksum' => hash_file('sha256', $letter->getRealPath()),
                'uploaded_by' => $supervisor->id,
            ]) : null;

            $candidate->update([
                'offered_position' => $data['posisi'],
                'unit' => $data['unit'] ?? null,
                'work_location' => $data['lokasi'],
                'work_mode' => $data['mode'],
                'employment_type' => $data['jenis'],
                'contract_months' => $data['jenis'] === 'kontrak' ? ($data['durasi'] ?? null) : null,
                'start_date' => $data['mulai'] ?? null,
                'job_description' => $data['deskripsi'],
                'compensation' => $data['kompensasi'] ?? null,
                'hr_contact_name' => $data['hr_nama'],
                'hr_contact_email' => $data['hr_email'],
                'offer_letter_id' => $stored?->id,
                'offered_at' => now(),
                'respond_by' => $data['batas'],
            ]);
            $candidate->moveTo('ditawari', $supervisor->id);

            $deadline = Carbon::parse($data['batas'])->locale('id')->translatedFormat('j F Y');
            ActivityLog::record('rekrutmen.tawar', 'Mengirim tawaran kerja '.$data['posisi'].' kepada '.$internship->student->name, $candidate);
            Notify::send($internship->student, 'Tawaran kerja dari '.$internship->placeName(), 'Posisi '.$data['posisi'].'. Mohon dijawab sebelum '.$deadline.'.', route('rekrutmen.tawaran'), 'star', 'green');

            return $candidate;
        });
    }

    /** Perpanjang batas waktu jawaban (juga membuka kembali tawaran yang kedaluwarsa). */
    public function extend(User $supervisor, Internship $internship, string $until): ConversionCandidate
    {
        $this->guardSupervisor($supervisor, $internship);
        $candidate = $this->candidateIn($internship, ['ditawari', 'kedaluwarsa']);

        $candidate->update(['respond_by' => $until]);
        if ($candidate->status === 'kedaluwarsa') {
            $candidate->moveTo('ditawari', $supervisor->id, 'Batas waktu diperpanjang');
        }

        $deadline = Carbon::parse($until)->locale('id')->translatedFormat('j F Y');
        ActivityLog::record('rekrutmen.perpanjang', 'Memperpanjang batas jawaban tawaran kerja '.$internship->student->name.' sampai '.$deadline, $candidate);
        Notify::send($internship->student, 'Batas jawaban tawaran kerja diperpanjang', 'Sekarang sampai '.$deadline.'.', route('rekrutmen.tawaran'), 'star', 'blue');

        return $candidate;
    }

    /** Mahasiswa menerima, atau menolak dengan alasan. */
    public function respond(User $student, Internship $internship, bool $accepted, ?string $reason = null, ?string $note = null): ConversionCandidate
    {
        abort_unless((int) $internship->student_id === (int) $student->id, 403);
        $candidate = $this->candidateIn($internship, ['ditawari']);
        if (! $candidate->isOpen()) {
            throw ValidationException::withMessages(['jawaban' => 'Batas waktu menjawab tawaran ini sudah lewat.']);
        }
        if (! $accepted && ! array_key_exists((string) $reason, ConversionCandidate::DECLINE_REASONS)) {
            throw ValidationException::withMessages(['alasan' => 'Pilih alasan menolak tawaran.']);
        }

        $candidate->update([
            'responded_at' => now(),
            'decline_reason' => $accepted ? null : $reason,
            'decline_note' => $accepted ? null : (filled($note) ? $note : null),
        ]);
        $candidate->moveTo($accepted ? 'diterima' : 'ditolak', $student->id, $accepted ? null : $candidate->declineReasonLabel());

        $name = $internship->student->listName();
        ActivityLog::record('rekrutmen.'.($accepted ? 'terima' : 'tolak'), $internship->student->name.($accepted ? ' menerima' : ' menolak').' tawaran kerja', $candidate);
        Notify::send(Notify::parties($internship, ['supervisor', 'lecturer']),
            $name.($accepted ? ' menerima' : ' menolak').' tawaran kerja',
            $accepted ? $candidate->offered_position : 'Alasan: '.mb_strtolower((string) $candidate->declineReasonLabel()).'.',
            route('pantau.show', $internship).'#rekrutmen', 'star', $accepted ? 'green' : 'blue');

        return $candidate;
    }

    public function cancel(User $supervisor, Internship $internship): void
    {
        $this->guardSupervisor($supervisor, $internship);
        $candidate = $this->candidateIn($internship, ['direkomendasikan']);

        ActivityLog::record('rekrutmen.batal', 'Membatalkan rekomendasi '.$internship->student->name, $candidate);
        $candidate->delete();
    }

    /** Tawaran yang lewat batas waktu tanpa jawaban menjadi kedaluwarsa (dijalankan pengingat harian). */
    public static function expireOverdue(?Carbon $today = null): int
    {
        $today = ($today ?? Carbon::today())->copy()->startOfDay();
        $count = 0;

        ConversionCandidate::with('internship.student', 'internship.supervisor')
            ->where('status', 'ditawari')->whereNotNull('respond_by')->whereDate('respond_by', '<', $today)
            ->each(function (ConversionCandidate $candidate) use (&$count) {
                $candidate->moveTo('kedaluwarsa', null, 'Tidak dijawab sampai batas waktu');
                $internship = $candidate->internship;
                Notify::send(Notify::parties($internship, ['supervisor']), 'Tawaran kerja '.$internship->student->listName().' kedaluwarsa',
                    'Tidak ada jawaban sampai batas waktu. Anda bisa memperpanjangnya.', route('pantau.show', $internship).'#rekrutmen', 'clock', 'amber');
                $count++;
            });

        return $count;
    }

    private function guardSupervisor(User $supervisor, Internship $internship): void
    {
        abort_unless($supervisor->role === 'supervisor' && $internship->isSupervisedBy($supervisor), 403);
        if (! in_array($internship->status, ['berjalan', 'selesai'], true)) {
            throw ValidationException::withMessages(['rekrutmen' => 'Rekomendasi hanya untuk KP yang berjalan atau selesai.']);
        }
    }

    /** @param  array<int, string>  $statuses */
    private function candidateIn(Internship $internship, array $statuses): ConversionCandidate
    {
        $candidate = $internship->conversion()->first();
        if (! $candidate || ! in_array($candidate->status, $statuses, true)) {
            throw ValidationException::withMessages(['rekrutmen' => 'Status rekrutmen sudah berubah. Muat ulang halaman.']);
        }

        return $candidate;
    }
}

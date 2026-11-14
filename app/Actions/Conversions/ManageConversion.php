<?php

namespace App\Actions\Conversions;

use App\Models\ActivityLog;
use App\Models\ConversionCandidate;
use App\Models\Internship;
use App\Models\User;
use App\Support\Notify;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Rekomendasi jadi karyawan: Company Supervisor merekomendasikan, lalu menawarkan kerja.
 * Mahasiswa baru melihatnya setelah ditawari, dan bisa menjawab Terima / Tolak sendiri.
 * Alur: direkomendasikan -> ditawari -> diterima | ditolak.
 */
class ManageConversion
{
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

    public function offer(User $supervisor, Internship $internship, string $position, ?string $note): ConversionCandidate
    {
        $this->guardSupervisor($supervisor, $internship);
        $candidate = $this->candidateIn($internship, ['direkomendasikan']);

        $candidate->update(['offered_position' => $position]);
        $candidate->moveTo('ditawari', $supervisor->id, $note ?: null);

        ActivityLog::record('rekrutmen.tawar', 'Menawarkan posisi '.$position.' kepada '.$internship->student->name, $candidate);
        Notify::send($internship->student, 'Tawaran kerja dari '.$internship->placeName(), 'Posisi '.$position.'. Jawab dari dashboard.', route('dashboard'), 'star', 'green');

        return $candidate;
    }

    /** Hasil tawaran dicatat supervisor (mis. dijawab di luar aplikasi). */
    public function record(User $supervisor, Internship $internship, bool $accepted): ConversionCandidate
    {
        $this->guardSupervisor($supervisor, $internship);

        return $this->finish($supervisor, $internship, $accepted);
    }

    /** Mahasiswa menjawab tawaran kerja. */
    public function respond(User $student, Internship $internship, bool $accepted): ConversionCandidate
    {
        abort_unless((int) $internship->student_id === (int) $student->id, 403);

        $candidate = $this->finish($student, $internship, $accepted);
        Notify::send(Notify::parties($internship, ['supervisor', 'lecturer']),
            $internship->student->listName().($accepted ? ' menerima' : ' menolak').' tawaran kerja', $candidate->offered_position, route('pantau.show', $internship), 'star', $accepted ? 'green' : 'blue');

        return $candidate;
    }

    public function cancel(User $supervisor, Internship $internship): void
    {
        $this->guardSupervisor($supervisor, $internship);
        $candidate = $this->candidateIn($internship, ['direkomendasikan']);

        ActivityLog::record('rekrutmen.batal', 'Membatalkan rekomendasi '.$internship->student->name, $candidate);
        $candidate->delete();
    }

    private function finish(User $actor, Internship $internship, bool $accepted): ConversionCandidate
    {
        $candidate = $this->candidateIn($internship, ['ditawari']);
        $candidate->moveTo($accepted ? 'diterima' : 'ditolak', $actor->id);
        ActivityLog::record('rekrutmen.'.($accepted ? 'terima' : 'tolak'), $internship->student->name.($accepted ? ' menerima' : ' menolak').' tawaran kerja', $candidate);

        return $candidate;
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

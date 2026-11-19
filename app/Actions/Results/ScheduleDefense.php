<?php

namespace App\Actions\Results;

use App\Models\ActivityLog;
use App\Models\DefenseSchedule;
use App\Models\Internship;
use App\Models\User;
use App\Support\Notify;
use Illuminate\Validation\ValidationException;

/** Kaprodi menjadwalkan sidang/presentasi KP. Mahasiswa, dosen pembimbing, dan dosen penguji diberi tahu. */
class ScheduleDefense
{
    /** Boleh dijadwalkan: KP selesai, atau berjalan dan berakhir dalam 30 hari ke depan. */
    public static function schedulable(Internship $internship): bool
    {
        return $internship->status === 'selesai'
            || ($internship->status === 'berjalan' && $internship->end_date->lte(today()->addDays(30)));
    }

    /** @param  array{tanggal: string, jam: string, cara: string, tempat: string, penguji?: ?int, catatan?: ?string}  $data */
    public function handle(User $kaprodi, Internship $internship, array $data): DefenseSchedule
    {
        if (! self::schedulable($internship)) {
            throw ValidationException::withMessages(['tanggal' => 'Sidang bisa dijadwalkan paling cepat 30 hari sebelum KP berakhir.']);
        }
        if ($internship->defenseScore) {
            throw ValidationException::withMessages(['tanggal' => 'Sidang ini sudah dinilai.']);
        }

        $existing = $internship->defenseSchedule;
        $schedule = DefenseSchedule::updateOrCreate(['internship_id' => $internship->id], [
            'scheduled_at' => $data['tanggal'].' '.$data['jam'],
            'mode' => $data['cara'],
            'place' => $data['tempat'],
            'examiner_id' => $data['penguji'] ?? null,
            'note' => filled($data['catatan'] ?? null) ? $data['catatan'] : null,
            'created_by' => $kaprodi->id,
        ])->load('examiner');

        $student = $internship->student;
        $verb = $existing ? 'diubah' : 'dijadwalkan';
        ActivityLog::record('sidang.jadwal', 'Sidang KP '.$student->name.' '.$verb.': '.$schedule->whenLabel(), $schedule);

        $where = ($schedule->isOnline() ? 'Daring' : 'Ruang '.$schedule->place);
        Notify::send($student, 'Sidang KP '.$verb, $schedule->whenLabel().' · '.$where, route('kp.status'), 'calendar', 'blue', email: true);
        $staff = collect([$internship->lecturer, $schedule->examiner])->filter()->unique('id');
        Notify::send($staff, 'Sidang KP '.$student->listName().' '.$verb, $schedule->whenLabel().' · '.$where, route('pantau.show', $internship), 'calendar', 'blue', email: true);

        return $schedule;
    }
}

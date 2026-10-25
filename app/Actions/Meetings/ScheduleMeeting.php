<?php

namespace App\Actions\Meetings;

use App\Models\ActivityLog;
use App\Models\User;
use App\Models\WeeklyMeeting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/** Mentor mengatur meeting minggu berjalan; satu link boleh dipakai beberapa mahasiswa. */
class ScheduleMeeting
{
    /**
     * @param  Collection<int, \App\Models\Internship>  $internships  sudah lolos pengecekan hak akses
     */
    public function handle(User $actor, Collection $internships, string $day, string $time, string $url): void
    {
        DB::transaction(function () use ($actor, $internships, $day, $time, $url) {
            foreach ($internships as $internship) {
                WeeklyMeeting::updateOrCreate(
                    ['internship_id' => $internship->id, 'week_number' => $internship->currentWeek()],
                    ['day' => $day, 'time' => $time, 'url' => $url, 'created_by' => $actor->id]
                );
            }

            ActivityLog::record(
                'meeting.atur',
                'Mengatur meeting minggu ini untuk '.$internships->map(fn ($i) => $i->student->name)->implode(', ')
            );
        });
    }
}

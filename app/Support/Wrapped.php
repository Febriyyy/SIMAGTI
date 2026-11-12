<?php

namespace App\Support;

use App\Models\Internship;
use Illuminate\Support\Carbon;

/**
 * Data "KP Wrapped": rangkuman akhir KP ala Spotify Wrapped (hanya untuk KP yang sudah selesai).
 * Dihitung dari catatan kegiatan, task, goal, bimbingan, dan penilaian.
 */
class Wrapped
{
    /** @return array<string, mixed> */
    public static function build(Internship $internship): array
    {
        $internship->loadMissing(['student', 'company', 'assessment']);
        $start = $internship->start_date->copy()->startOfDay();

        $entries = $internship->timeEntries()->get(['id', 'work_date']);
        $tasks = $internship->tasks()->get(['id', 'status', 'completed_at']);
        $done = $tasks->where('status', 'completed');

        // Minggu paling produktif: catatan + task selesai per minggu KP
        $byWeek = [];
        foreach ($entries as $e) {
            $w = intdiv((int) $start->diffInDays(Carbon::parse($e->work_date)->startOfDay()), 7) + 1;
            $byWeek[$w] = ($byWeek[$w] ?? 0) + 1;
        }
        foreach ($done as $t) {
            if ($t->completed_at) {
                $w = intdiv((int) $start->diffInDays($t->completed_at->copy()->startOfDay()), 7) + 1;
                $byWeek[$w] = ($byWeek[$w] ?? 0) + 1;
            }
        }
        $bestWeek = null;
        if ($byWeek) {
            arsort($byWeek);
            $number = (int) array_key_first($byWeek);
            $from = $start->copy()->addWeeks($number - 1);
            $bestWeek = ['number' => $number, 'count' => $byWeek[$number], 'from' => $from, 'to' => $from->copy()->addDays(6)];
        }

        // Hari favorit: hari dalam seminggu dengan catatan terbanyak
        $byDay = $entries->groupBy(fn ($e) => Carbon::parse($e->work_date)->dayOfWeekIso)->map->count()->sortDesc();
        $favoriteDay = $byDay->isEmpty() ? null : [
            'name' => Carbon::now()->startOfWeek(Carbon::MONDAY)->addDays((int) $byDay->keys()->first() - 1)->locale('id')->translatedFormat('l'),
            'count' => (int) $byDay->first(),
        ];

        $contribution = Contribution::build($internship, $internship->end_date->copy());
        $goals = $internship->goals()->get();
        $assessment = $internship->assessment;

        return [
            'name' => $internship->student->name,
            'firstName' => $internship->student->firstName(),
            'place' => $internship->placeName(),
            'position' => $internship->position,
            'year' => $internship->end_date->format('Y'),
            'period' => $internship->periodLabel(),
            'weeks' => $internship->totalWeeks(),
            'days' => $entries->pluck('work_date')->map(fn ($d) => Carbon::parse($d)->toDateString())->unique()->count(),
            'entries' => $entries->count(),
            'tasks' => $tasks->count(),
            'done' => $done->count(),
            'percent' => $tasks->count() ? (int) round($done->count() / $tasks->count() * 100) : 0,
            'bestWeek' => $bestWeek,
            'favoriteDay' => $favoriteDay,
            'longest' => $contribution['longest'],
            'goals' => $goals->whereNotNull('achieved_at')->pluck('title')->values()->all(),
            'goalsTotal' => $goals->count(),
            'guidance' => $internship->guidanceSessions()->where('status', 'confirmed')->count(),
            'grade' => $internship->final_grade ?? ($assessment?->isValidated() ? $assessment->letter_grade : null),
            'score' => $internship->final_score ?? ($assessment?->isValidated() ? $assessment->final_score : null),
            'result' => $internship->result ? $internship->resultLabel() : null,
        ];
    }
}

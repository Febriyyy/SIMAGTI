<?php

namespace App\Support;

use App\Models\Internship;
use Illuminate\Support\Carbon;

/**
 * Peta kontribusi ala GitHub untuk satu KP: satu kotak per hari sepanjang periode (sampai hari ini bila masih berjalan),
 * disusun per minggu (kolom) dan hari Senin-Minggu (baris). Nilai harian = catatan kegiatan + task yang selesai hari itu.
 */
class Contribution
{
    /**
     * @return array{weeks: list<list<?array{date: Carbon, count: int, level: int, future: bool}>>, months: list<array{label: string, col: int}>,
     *               total: int, activeDays: int, longest: int, current: int, days: int}
     */
    public static function build(Internship $internship, ?Carbon $today = null): array
    {
        $today = ($today ?? Carbon::today())->copy()->startOfDay();
        $start = $internship->start_date->copy()->startOfDay();
        $end = $internship->end_date->copy()->startOfDay();

        $entries = $internship->timeEntries()->selectRaw('DATE(work_date) as d, COUNT(*) as c')->groupBy('d')->pluck('c', 'd');
        $done = $internship->tasks()->whereNotNull('completed_at')->selectRaw('DATE(completed_at) as d, COUNT(*) as c')->groupBy('d')->pluck('c', 'd');

        $gridStart = $start->copy()->startOfWeek(Carbon::MONDAY);
        $gridEnd = $end->copy()->endOfWeek(Carbon::SUNDAY)->startOfDay();

        $weeks = [];
        $months = [];
        $total = $active = $longest = $run = 0;
        $lastMonth = null;

        for ($day = $gridStart->copy(), $col = 0; $day->lte($gridEnd); $col++) {
            $week = [];
            for ($i = 0; $i < 7; $i++, $day->addDay()) {
                if ($day->lt($start) || $day->gt($end)) {
                    $week[] = null;

                    continue;
                }
                $key = $day->toDateString();
                $count = (int) ($entries[$key] ?? 0) + (int) ($done[$key] ?? 0);
                $future = $day->gt($today);
                if (! $future) {
                    $total += $count;
                    $active += $count > 0 ? 1 : 0;
                    $run = $count > 0 ? $run + 1 : ($day->isWeekend() ? $run : 0);
                    $longest = max($longest, $run);
                }
                $week[] = ['date' => $day->copy(), 'count' => $count, 'level' => self::level($count), 'future' => $future];

                if ($lastMonth !== $day->month && $day->day <= 7) {
                    $months[] = ['label' => $day->locale('id')->translatedFormat('M'), 'col' => $col];
                    $lastMonth = $day->month;
                }
            }
            $weeks[] = $week;
        }

        // Rentetan saat ini: hari kerja berturut-turut yang berisi kegiatan, dihitung mundur dari hari ini (akhir pekan dilewati)
        $current = 0;
        for ($day = ($today->lt($end) ? $today : $end)->copy(); $day->gte($start); $day->subDay()) {
            $count = (int) ($entries[$day->toDateString()] ?? 0) + (int) ($done[$day->toDateString()] ?? 0);
            if ($count > 0) {
                $current++;
            } elseif (! $day->isWeekend() && ! $day->isSameDay($today)) {
                break;
            }
        }

        return [
            'weeks' => $weeks,
            'months' => $months,
            'total' => $total,
            'activeDays' => $active,
            'longest' => $longest,
            'current' => $current,
            'days' => (int) $start->diffInDays($end) + 1,
        ];
    }

    private static function level(int $count): int
    {
        return match (true) {
            $count === 0 => 0,
            $count === 1 => 1,
            $count === 2 => 2,
            $count <= 4 => 3,
            default => 4,
        };
    }
}

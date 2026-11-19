<?php

namespace App\Support;

use App\Models\DefenseSchedule;
use App\Models\GuidanceSession;
use Illuminate\Support\Carbon;

/**
 * Tautan "Tambah ke Google Calendar" (tanpa API dan tanpa biaya): membuka formulir acara Google Calendar
 * yang sudah terisi, lalu pengguna menekan Simpan di akunnya sendiri.
 */
class CalendarLink
{
    public static function google(string $title, Carbon $start, int $minutes, ?string $details = null, ?string $location = null): string
    {
        $fmt = fn (Carbon $t) => $t->copy()->utc()->format('Ymd\THis\Z');

        return 'https://calendar.google.com/calendar/render?'.http_build_query(array_filter([
            'action' => 'TEMPLATE',
            'text' => $title,
            'dates' => $fmt($start).'/'.$fmt($start->copy()->addMinutes($minutes)),
            'details' => $details,
            'location' => $location,
        ]), '', '&', PHP_QUERY_RFC3986);
    }

    public static function guidance(GuidanceSession $session): string
    {
        $details = trim(implode("\n\n", array_filter([
            $session->notes,
            $session->meeting_link ? 'Tautan rapat: '.$session->meeting_link : null,
            'Detail: '.route('bimbingan.index'),
        ])));

        return self::google('Bimbingan KP: '.$session->topic, $session->startsAt(), 60, $details, $session->meeting_link ?: $session->modeLabel());
    }

    public static function defense(DefenseSchedule $schedule, string $studentName): string
    {
        return self::google('Sidang KP '.$studentName, $schedule->scheduled_at, 90, $schedule->note, $schedule->place);
    }
}

<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/** Selisih hari kalender dari hari ini, dipakai konsisten di tabel dan peringatan. */
class Relative
{
    public static function daysAgo(CarbonInterface $date): int
    {
        return (int) $date->copy()->startOfDay()->diffInDays(Carbon::today());
    }

    public static function label(?CarbonInterface $date): string
    {
        if ($date === null) {
            return '–';
        }

        return match ($days = self::daysAgo($date)) {
            0 => 'Hari ini',
            1 => 'Kemarin',
            default => "{$days} hari lalu",
        };
    }
}

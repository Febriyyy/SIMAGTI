<?php

namespace App\Support;

use App\Models\Internship;
use Illuminate\Support\Carbon;

/**
 * Periode kerja ("sprint") mahasiswa menurut ritme yang ia pilih: harian, mingguan, atau bulanan.
 * Mingguan mengikuti minggu KP (minggu ke-1 dimulai di tanggal mulai KP) supaya sama dengan "minggu ke-" di tempat lain.
 */
class Period
{
    public function __construct(
        public readonly Carbon $start,
        public readonly Carbon $end,
        public readonly string $label,
        public readonly string $short,
    ) {}

    /** Periode ke-($offset) dari periode yang memuat $date (0 = periode ini, -1 = sebelumnya). */
    public static function for(Internship $internship, ?Carbon $date = null, int $offset = 0): self
    {
        $date = ($date ?? Carbon::today())->copy()->startOfDay();

        return match ($internship->cadence) {
            'daily' => self::day($date->addDays($offset)),
            'monthly' => self::month($date->startOfMonth()->addMonthsNoOverflow($offset)),
            default => self::week($internship, $date->addWeeks($offset)),
        };
    }

    private static function day(Carbon $date): self
    {
        $local = $date->copy()->locale('id');

        return new self($date->copy(), $date->copy()->endOfDay(), $local->translatedFormat('l, j F Y'), $local->translatedFormat('j M'));
    }

    private static function month(Carbon $date): self
    {
        $local = $date->copy()->locale('id');

        return new self($date->copy()->startOfMonth(), $date->copy()->endOfMonth(), $local->translatedFormat('F Y'), $local->translatedFormat('M Y'));
    }

    private static function week(Internship $internship, Carbon $date): self
    {
        $week = $internship->weekOf($date->lt($internship->start_date) ? $internship->start_date : $date);
        $start = $internship->start_date->copy()->startOfDay()->addWeeks($week - 1);
        $end = $start->copy()->addDays(6)->endOfDay();
        $range = $start->copy()->locale('id')->translatedFormat('j M').' – '.$end->copy()->locale('id')->translatedFormat('j M');

        return new self($start, $end, 'Minggu ke-'.$week.' · '.$range, 'Minggu ke-'.$week);
    }
}

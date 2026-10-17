<?php

namespace App\Support;

/** Format angka gaya Indonesia: 87,5 · 90 · 1.234,25 (nol di belakang koma dibuang). */
class Num
{
    public static function short(int|float|string|null $value, int $decimals = 2): string
    {
        if ($value === null || $value === '') {
            return '–';
        }

        $formatted = number_format((float) $value, $decimals, ',', '.');

        return str_contains($formatted, ',') ? rtrim(rtrim($formatted, '0'), ',') : $formatted;
    }
}

<?php

namespace App\Support;

class Greeting
{
    /** Sapaan menurut jam (ikut APP_TIMEZONE). */
    public static function now(): string
    {
        $hour = (int) now()->format('G');

        return match (true) {
            $hour < 11 => 'Selamat pagi',
            $hour < 15 => 'Selamat siang',
            $hour < 18 => 'Selamat sore',
            default => 'Selamat malam',
        };
    }
}

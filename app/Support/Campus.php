<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Identitas kampus dan aturan KP. Diambil dari tabel settings bila ada, jika tidak dari config/simagti.php.
 * Satu-satunya tempat yang perlu diubah saat sistem dipakai kampus lain.
 */
class Campus
{
    /** @var array<string, mixed> */
    private static array $cache = [];

    public static function get(string $key): mixed
    {
        if (! array_key_exists($key, self::$cache)) {
            $default = config('simagti.'.$key);

            try {
                self::$cache[$key] = Setting::get('simagti.'.$key, $default);
            } catch (\Throwable) {
                self::$cache[$key] = $default; // tabel belum ada (misalnya saat migrasi)
            }
        }

        return self::$cache[$key];
    }

    public static function university(): string
    {
        return (string) self::get('campus.university');
    }

    public static function faculty(): string
    {
        return (string) self::get('campus.faculty');
    }

    public static function city(): string
    {
        return (string) self::get('campus.city');
    }

    public static function rule(string $name): mixed
    {
        return self::get('rules.'.$name);
    }

    public static function flush(): void
    {
        self::$cache = [];
    }
}

<?php

namespace App\Support;

use App\Models\Internship;
use App\Models\User;
use App\Notifications\KpNotice;
use Illuminate\Support\Collection;

/** Mengirim kabar ke lonceng notifikasi. Penerima kosong atau pelaku sendiri dilewati. */
class Notify
{
    /** @param  User|iterable<User>|null  $to */
    public static function send(User|iterable|null $to, string $title, ?string $body = null, ?string $url = null, string $icon = 'mail', string $tone = 'blue'): void
    {
        $users = $to instanceof User ? collect([$to]) : collect($to ?? []);
        $me = auth()->id();

        $users->filter()->unique('id')
            ->reject(fn (User $u) => $me !== null && (int) $u->id === (int) $me)
            ->each(fn (User $u) => $u->notify(new KpNotice($title, $body, $url, $icon, $tone)));
    }

    /** Pihak yang terlibat dalam satu KP: mahasiswa, supervisor, dosen pembimbing. */
    public static function parties(Internship $internship, array $who = ['student', 'supervisor', 'lecturer']): Collection
    {
        $internship->loadMissing(['student', 'supervisor', 'lecturer']);

        return collect($who)->map(fn ($w) => $internship->{$w})->filter()->values();
    }

    /** Semua pengguna aktif dengan peran tertentu. */
    public static function role(string $role): Collection
    {
        return User::where('role', $role)->where('is_active', true)->get();
    }
}

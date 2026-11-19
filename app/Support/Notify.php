<?php

namespace App\Support;

use App\Models\Internship;
use App\Models\User;
use App\Notifications\KpEmail;
use App\Notifications\KpNotice;
use Illuminate\Support\Collection;
use Throwable;

/**
 * Mengirim kabar ke lonceng notifikasi. Penerima kosong atau pelaku sendiri dilewati.
 * email: true menambahkan salinan email untuk kabar penting; kegagalan SMTP dicatat, tidak menggagalkan aksi.
 */
class Notify
{
    /** @param  User|iterable<User>|null  $to */
    public static function send(User|iterable|null $to, string $title, ?string $body = null, ?string $url = null, string $icon = 'mail', string $tone = 'blue', bool $email = false): void
    {
        $users = $to instanceof User ? collect([$to]) : collect($to ?? []);
        $me = auth()->id();

        $users->filter()->unique('id')
            ->reject(fn (User $u) => $me !== null && (int) $u->id === (int) $me)
            ->each(function (User $u) use ($title, $body, $url, $icon, $tone, $email) {
                $u->notify(new KpNotice($title, $body, $url, $icon, $tone));
                if ($email && $u->is_active && filled($u->email)) {
                    try {
                        $u->notify(new KpEmail($title, $body, $url));
                    } catch (Throwable $e) {
                        report($e);
                    }
                }
            });
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

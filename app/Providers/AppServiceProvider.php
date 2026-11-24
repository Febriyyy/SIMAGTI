<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Di server (APP_ENV=production) semua tautan dan aset memakai HTTPS,
        // termasuk bila hosting meneruskan permintaan lewat proxy tanpa memberi tahu skemanya.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Email lupa kata sandi dalam bahasa Indonesia
        ResetPassword::toMailUsing(fn (object $user, string $token) => (new MailMessage)
            ->subject('Atur ulang kata sandi · '.config('app.name'))
            ->greeting('Halo, '.($user->name ?? ''))
            ->line('Kami menerima permintaan untuk mengatur ulang kata sandi akun SIMAGTI Anda.')
            ->action('Atur ulang kata sandi', route('password.reset', ['token' => $token, 'email' => $user->email]))
            ->line('Tautan ini berlaku 60 menit dan hanya bisa dipakai sekali.')
            ->line('Jika Anda tidak meminta ini, abaikan email ini. Kata sandi Anda tidak berubah.')
            ->salutation('Salam, SIMAGTI'));
    }
}

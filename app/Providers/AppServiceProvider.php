<?php

namespace App\Providers;

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
    }
}

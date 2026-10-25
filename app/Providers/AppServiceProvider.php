<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
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
        // Older MySQL/MariaDB (e.g. MariaDB 5.5) index at most 767 bytes per column;
        // 191 utf8mb4 characters (764 bytes) is the longest indexed string that fits.
        Schema::defaultStringLength(191);

        // Behind Plesk's proxy Laravel may see plain http; canonical links, the sitemap
        // and redirects must still use https when the site runs on https
        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }
    }
}

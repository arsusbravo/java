<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
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
    }
}

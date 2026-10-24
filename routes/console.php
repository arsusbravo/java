<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Hotel prices and Agoda guest ratings (needs the server cron for `schedule:run`)
Schedule::command('agoda:prices')->dailyAt('04:00')->withoutOverlapping();

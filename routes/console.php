<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Schedule::command('backup:run --only-db')->dailyAt('01:15')->withoutOverlapping();
Schedule::command('backup:run --only-files')->dailyAt('01:45')->withoutOverlapping();
Schedule::command('backup:clean')->dailyAt('02:30')->withoutOverlapping();
Schedule::command('backup:monitor')->dailyAt('07:00')->withoutOverlapping();

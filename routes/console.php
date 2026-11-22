<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('stock:check-alerts')->dailyAt('07:00');
Schedule::command('licenses:check-expiry')->dailyAt('07:10');
Schedule::command('backup:clean')->dailyAt('01:30');
Schedule::command('backup:run --only-db')->dailyAt('02:00');
Schedule::command('backup:run')->weeklyOn(1, '02:30');
Schedule::command('backup:monitor')->dailyAt('03:00');

<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Process queued campaign jobs every minute (no separate queue worker needed if cron runs schedule:run).
// Use queue:work-once wrapper so --stop-when-empty is not passed with a value (avoids Symfony "option does not accept a value" in subprocess).
Schedule::command('queue:work-once')->everyMinute()->withoutOverlapping();

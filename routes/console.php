<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Process queued campaign jobs every minute (no separate queue worker needed if cron runs schedule:run).
Schedule::command('queue:work', [
    '--stop-when-empty' => true,
    '--max-time' => 55,
])->everyMinute()->withoutOverlapping();

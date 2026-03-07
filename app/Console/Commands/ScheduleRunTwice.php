<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class ScheduleRunTwice extends Command
{
    protected $signature = 'schedule:run-twice';

    protected $description = 'Run the scheduler twice with a 30-second gap (so one cron entry gives ~30s queue pickup).';

    public function handle(): int
    {
        Artisan::call('schedule:run');
        sleep(30);
        Artisan::call('schedule:run');

        return self::SUCCESS;
    }
}

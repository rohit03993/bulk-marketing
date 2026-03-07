<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

/**
 * Wrapper so the scheduler can run the queue worker without passing
 * --stop-when-empty=1 (which some environments reject; the option accepts no value).
 */
class QueueWorkOnce extends Command
{
    protected $signature = 'queue:work-once';

    protected $description = 'Run queue:work --stop-when-empty --max-time=55 (for scheduler).';

    public function handle(): int
    {
        Artisan::call('queue:work', [
            '--stop-when-empty' => true,
            '--max-time' => 55,
        ]);

        return self::SUCCESS;
    }
}

<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;

class RunCampaignJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Allow up to 5 minutes per batch (e.g. 200 messages). */
    public int $timeout = 300;

    public int $tries = 3;

    public function __construct(
        public int $campaignId
    ) {}

    public function handle(): void
    {
        Artisan::call('campaigns:run', ['campaign' => $this->campaignId]);
    }
}

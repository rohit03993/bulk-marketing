<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Campaign batch sizing
    |--------------------------------------------------------------------------
    |
    | How many pending recipients are processed per `campaigns:run` execution.
    | Smaller batches reduce queue-job runtime and timeout risk.
    |
    */

    'batch_size' => max(1, (int) env('CAMPAIGN_BATCH_SIZE', 10)),

    /*
    |--------------------------------------------------------------------------
    | Delay before queueing next batch
    |--------------------------------------------------------------------------
    |
    | After one batch finishes, we can wait before queueing the next batch.
    | Example: 300 seconds means "send one batch, then next batch after 5 min".
    |
    */

    'next_batch_delay_seconds' => max(0, (int) env('CAMPAIGN_NEXT_BATCH_DELAY_SECONDS', 300)),

    /*
    |--------------------------------------------------------------------------
    | Campaign send throttling (Aisensy / HTTP)
    |--------------------------------------------------------------------------
    |
    | After every N messages processed in one `campaigns:run` batch, the worker
    | sleeps briefly. This spreads load on your server and the provider API.
    |
    | Set pause_after_messages to 0 to disable.
    |
    */

    'pause_after_messages' => max(0, (int) env('CAMPAIGN_PAUSE_AFTER_MESSAGES', 0)),

    'pause_seconds' => max(0.0, (float) env('CAMPAIGN_PAUSE_SECONDS', 0)),

];

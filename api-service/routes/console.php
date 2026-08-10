<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

//Artisan::command('inspire', function () {
//    Log::info('omid1111111');
//    $this->comment('asdasd');
//})->purpose('Display an inspiring quote')->everyTenSeconds();


Schedule::command('bot:sync-sell-orders')
    ->everyThirtyMinutes()
    ->withoutOverlapping()
    // Stream command stdout/stderr into the container PID-1 streams so
    // `docker compose logs -f api-cron` shows each run's summary line.
    ->sendOutputTo('/proc/1/fd/1')
    ->appendOutputTo('/proc/1/fd/2');

Schedule::command('bot:scan-buy-triggers')->everyMinute()->withoutOverlapping();

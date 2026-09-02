<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

//Artisan::command('inspire', function () {
//    Log::info('omid1111111');
//    $this->comment('asdasd');
//})->purpose('Display an inspiring quote')->everyTenSeconds();


Schedule::command('bot:sync-sell-orders --limit=500')
    ->everyFiveMinutes()
    // Default mutex TTL is 24h; a killed api-cron run would skip this job
    // silently until then. 10 min is enough for one poll batch to finish,
    // and recovers on the next */5 tick after a crash.
    ->withoutOverlapping(10)
    // appendOutputTo overwrites sendOutputTo — use only this. >> PID-1
    // stdout so `docker compose logs -f api-cron` shows the summary line.
    ->appendOutputTo('/proc/1/fd/1');

Schedule::command('bot:scan-buy-triggers')
    ->everyMinute()
    // Default mutex TTL is 24h. A killed api-cron run (deploy/restart) left
    // this lock behind and skipped the scan silently until the next day.
    // 5 min is far more than one scan+dispatch needs.
    ->withoutOverlapping(5)
    ->appendOutputTo('/proc/1/fd/1');

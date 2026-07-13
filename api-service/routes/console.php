<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

//Artisan::command('inspire', function () {
//    Log::info('omid1111111');
//    $this->comment('asdasd');
//})->purpose('Display an inspiring quote')->everyTenSeconds();


Schedule::command('bot:sync-sell-orders')->everyMinute()->withoutOverlapping();
Schedule::command('bot:scan-buy-triggers')->everyMinute()->withoutOverlapping();

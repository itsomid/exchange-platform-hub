<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();
        $schedule->command('fetch:market-history')->everyFiveMinutes();
        $schedule->command('exchange:fetch-min-otc-amount coinex')->dailyAt('01:00');
        $schedule->command('exchange:fetch-deposit-withdrawal-config coinex')->dailyAt('02:00');
        $schedule->command('bitexroom:transfer-to-hot-wallet')->everyFiveMinutes();
//        $schedule->command('backup:clean')->daily()->at('03:00');
//        $schedule->command('backup:run')->daily()->at('04:00');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}

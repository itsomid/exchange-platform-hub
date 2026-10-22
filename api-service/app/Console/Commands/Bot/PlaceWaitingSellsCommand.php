<?php

namespace App\Console\Commands\Bot;

use App\Models\Bot\BotGlobalSettings;
use App\Models\Currency;
use App\Services\Bot\WaitingSellTiersService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Places WAITING sell tiers (priced above Binance's PERCENT_PRICE_BY_SIDE
 * ceiling when created) once the market price brings them in range, or
 * market-sells them when the price already passed the target.
 * Runs every minute via routes/console.php while
 * bot_global_settings.waiting_sells_auto_place is on.
 */
class PlaceWaitingSellsCommand extends Command
{
    protected $signature = 'bot:place-waiting-sells {--force : Run even when auto placement is turned off}';

    protected $description = 'Place WAITING bot sell tiers on the reference exchange once their price is in range';

    public function handle(WaitingSellTiersService $service): int
    {
        $startedAt = now();

        if (! $this->option('force') && ! BotGlobalSettings::current()->waiting_sells_auto_place) {
            $this->info('bot:place-waiting-sells disabled');
            $service->rememberLastRun(['at' => $startedAt->toIso8601String(), 'disabled' => true]);

            return self::SUCCESS;
        }

        $budget = max(1, (int) config('smart-bot.waiting.max_per_run', 200));
        $totals = ['currencies' => 0, 'waiting' => 0, 'placed' => 0, 'completed' => 0, 'rejected' => 0, 'still_waiting' => 0];
        $coins  = [];

        foreach ($service->currencyIds() as $currencyId) {
            $currency = Currency::find($currencyId);
            if (! $currency) {
                continue;
            }

            try {
                $summary = $service->placeCurrency($currency, $budget);
            } catch (\Throwable $e) {
                Log::channel('smart-bot')->error('bot.waiting.currency_exception', [
                    'currency' => $currency->symbol,
                    'error'    => $e->getMessage(),
                ]);
                $coins[] = ['symbol' => strtoupper((string) $currency->symbol), 'error' => $e->getMessage()];
                continue;
            }

            $budget -= $summary['attempts'];
            $totals['currencies']++;
            foreach (['waiting', 'placed', 'completed', 'rejected', 'still_waiting'] as $key) {
                $totals[$key] += $summary[$key];
            }
            unset($summary['results']);
            $coins[] = $summary;

            if ($budget <= 0) {
                break;
            }
        }

        $service->rememberLastRun([
            'at'          => $startedAt->toIso8601String(),
            'disabled'    => false,
            'duration_ms' => (int) $startedAt->diffInMilliseconds(now()),
            'totals'      => $totals,
            'currencies'  => $coins,
        ]);

        $line = sprintf(
            'bot:place-waiting-sells currencies=%d waiting=%d placed=%d completed=%d rejected=%d still_waiting=%d',
            $totals['currencies'], $totals['waiting'], $totals['placed'], $totals['completed'], $totals['rejected'], $totals['still_waiting'],
        );
        $this->info($line);
        if ($totals['placed'] + $totals['completed'] + $totals['rejected'] > 0) {
            Log::channel('smart-bot')->info('bot.waiting.run', $totals);
        }

        return self::SUCCESS;
    }
}

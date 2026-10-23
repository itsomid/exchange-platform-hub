<?php

namespace App\Console\Commands\Bot;

use App\Models\Bot\BotGlobalSettings;
use App\Models\Currency;
use App\Services\Bot\WaitingSellTiersService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Places WAITING sell tiers once they fit on Binance: their price is under
 * the PERCENT_PRICE_BY_SIDE ceiling and an open-order slot is free (rotating
 * out far bot orders when the MAX_NUM_ORDERS limit is used up), or
 * market-sells them when the price already passed the target.
 * Runs every minute via routes/console.php while
 * bot_global_settings.waiting_sells_auto_place is on.
 */
class PlaceWaitingSellsCommand extends Command
{
    protected $signature = 'bot:place-waiting-sells {--force : Run even when auto placement is turned off}';

    protected $description = 'Place WAITING bot sell tiers on the reference exchange once they fit';

    public function handle(WaitingSellTiersService $service): int
    {
        $startedAt = now();

        $settings = BotGlobalSettings::current();
        if (! $this->option('force') && ! $settings->waiting_sells_auto_place) {
            $this->info('bot:place-waiting-sells disabled');
            $service->rememberLastRun(['at' => $startedAt->toIso8601String(), 'disabled' => true]);

            return self::SUCCESS;
        }

        $budget    = max(1, (int) $settings->waiting_max_per_run);
        $rotations = max(0, (int) $settings->waiting_rotation_max_per_run);
        $keys      = ['waiting', 'placed', 'completed', 'rejected', 'rotated', 'still_waiting', 'price_waiting', 'slot_waiting'];
        $totals    = ['currencies' => 0] + array_fill_keys($keys, 0);
        $coins     = [];

        foreach ($service->currencyIds() as $currencyId) {
            $currency = Currency::find($currencyId);
            if (! $currency) {
                continue;
            }

            try {
                $summary = $service->placeCurrency($currency, $budget, $rotations);
            } catch (\Throwable $e) {
                Log::channel('smart-bot')->error('bot.waiting.currency_exception', [
                    'currency' => $currency->symbol,
                    'error'    => $e->getMessage(),
                ]);
                $coins[] = ['currency_id' => $currency->id, 'symbol' => strtoupper((string) $currency->symbol), 'error' => $e->getMessage()];
                continue;
            }

            $budget    -= $summary['attempts'];
            $rotations  = max(0, $rotations - $summary['rotated']);
            $totals['currencies']++;
            foreach ($keys as $key) {
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
            'bot:place-waiting-sells currencies=%d waiting=%d placed=%d completed=%d rejected=%d rotated=%d still_waiting=%d price_waiting=%d slot_waiting=%d',
            $totals['currencies'], $totals['waiting'], $totals['placed'], $totals['completed'], $totals['rejected'],
            $totals['rotated'], $totals['still_waiting'], $totals['price_waiting'], $totals['slot_waiting'],
        );
        $this->info($line);
        if ($totals['placed'] + $totals['completed'] + $totals['rejected'] + $totals['rotated'] > 0) {
            Log::channel('smart-bot')->info('bot.waiting.run', $totals);
        }

        return self::SUCCESS;
    }
}

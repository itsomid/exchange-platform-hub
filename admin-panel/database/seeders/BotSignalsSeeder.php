<?php

namespace Database\Seeders;

use App\Models\Bot\BotSignal;
use App\Models\Currency;
use App\Models\ExchangePrice;
use App\Models\Market;
use Illuminate\Database\Seeder;

/**
 * Seeds bot_signals for every USDT market that currently has a live
 * exchange_prices row. Floor / ceiling are derived from the live price so the
 * signal is immediately "eligible" for the allocator (current price between
 * floor and ceiling).
 *
 * Idempotent: re-running updates rows in place (keyed on currency_id).
 */
class BotSignalsSeeder extends Seeder
{
    /**
     * Default sell ladder used for every seeded signal (percent mode).
     * Total share must sum to 100.
     */
    private const SELL_TARGETS = [
        ['trigger' => 5,  'share' => 30],
        ['trigger' => 10, 'share' => 30],
        ['trigger' => 20, 'share' => 40],
    ];

    public function run(): void
    {
        $markets = Market::query()
            ->where('quote_currency', 'USDT')
            ->where('is_active', true)
            ->get(['id', 'base_currency']);

        if ($markets->isEmpty()) {
            $this->command?->warn('BotSignalsSeeder: no active USDT markets found.');
            return;
        }

        $priority = 10;
        $created  = 0;

        foreach ($markets as $market) {
            $price = ExchangePrice::where('market_id', $market->id)->value('price');
            if ($price === null || (float) $price <= 0) {
                continue;
            }

            $currency = Currency::where('symbol', $market->base_currency)->first();
            if (! $currency) {
                continue;
            }

            // Skip stablecoins paired against USDT (no trading edge).
            if (in_array(strtoupper($currency->symbol), ['USDT', 'USDC', 'DAI', 'BUSD', 'FDUSD', 'TUSD'], true)) {
                continue;
            }

            $price        = (float) $price;
            $floor        = round($price * 0.70, 8);   // 30% below current
            $ceiling      = round($price * 1.50, 8);   // 50% above current
            $maxAllocPct  = $this->maxAllocationFor($priority);

            BotSignal::updateOrCreate(
                ['currency_id' => $currency->id],
                [
                    'priority'                => $priority,
                    'floor_price'             => $floor,
                    'ceiling_price'           => $ceiling,
                    'min_buy_amount_usdt'     => 5,
                    'max_allocation_percent'  => $maxAllocPct,
                    'sell_orders_count'       => count(self::SELL_TARGETS),
                    'sell_mode'               => 'percent',
                    'sell_targets'            => self::SELL_TARGETS,
                    'is_active'               => true,
                ]
            );

            $priority++;
                $created++;
        }

        $this->command?->info("BotSignalsSeeder: {$created} signals upserted from live exchange_prices.");
    }

    /**
     * Top-priority coins get a larger cap; lower-priority ones are throttled
     * so a single mid-cap can't absorb the entire allocation.
     */
    private function maxAllocationFor(int $priority): int
    {
        return match (true) {
            $priority <= 12 => 40,
            $priority <= 16 => 25,
            default         => 15,
        };
    }
}

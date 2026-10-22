<?php

namespace App\Services\Bot;

use App\Models\Bot\BotSellOrder;
use App\Models\Currency;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Drives WAITING sell tiers ({@see SellTierPlacementService}) onto the
 * exchange, per coin: lowest targets first, stopping at the first one still
 * above Binance's ceiling (every later target is higher). Used by
 * bot:place-waiting-sells and the admin "waiting tiers" page.
 */
class WaitingSellTiersService
{
    public const LAST_RUN_CACHE_KEY = 'bot:waiting-sells:last-run';

    private const SCALE = 8;

    public function __construct(
        private readonly SellTierPlacementService $placement,
        private readonly PriceFeed $priceFeed,
    ) {}

    /**
     * @return Collection<int,int> currency ids that have WAITING tiers
     */
    public function currencyIds(): Collection
    {
        return BotSellOrder::query()
            ->join('bot_buy_executions', 'bot_buy_executions.id', '=', 'bot_sell_orders.bot_buy_execution_id')
            ->where('bot_sell_orders.status', BotSellOrder::STATUS_OPEN)
            ->where('bot_sell_orders.placement_status', BotSellOrder::PLACEMENT_WAITING)
            ->distinct()
            ->orderBy('bot_buy_executions.currency_id')
            ->pluck('bot_buy_executions.currency_id')
            ->map(fn ($id) => (int) $id);
    }

    /**
     * Check one coin's WAITING tiers and place every one that is in range,
     * using at most $budget exchange orders.
     *
     * @return array{currency_id:int, symbol:string, waiting:int, placed:int, completed:int, rejected:int, still_waiting:int, attempts:int, live_price:?string, ceiling:?array, skipped:?string, results:list<array<string,mixed>>}
     */
    public function placeCurrency(Currency $currency, int $budget): array
    {
        $symbol  = strtoupper((string) $currency->symbol);
        $tiers   = $this->waitingTiers($currency->id);
        $summary = [
            'currency_id'   => $currency->id,
            'symbol'        => $symbol,
            'waiting'       => $tiers->count(),
            'placed'        => 0,
            'completed'     => 0,
            'rejected'      => 0,
            'still_waiting' => 0,
            'attempts'      => 0,
            'live_price'    => null,
            'ceiling'       => null,
            'skipped'       => null,
            'results'       => [],
        ];
        if ($tiers->isEmpty()) {
            return $summary;
        }

        $summary['skipped'] = $this->blockReason($currency->id);
        if ($summary['skipped'] !== null) {
            $summary['still_waiting'] = $tiers->count();

            return $summary;
        }

        $livePrice = $this->livePrice($currency);
        $ceiling   = $this->placement->ceiling($symbol.'USDT', $livePrice);
        $summary['live_price'] = $livePrice;
        $summary['ceiling']    = $ceiling;

        $hasLive = bccomp($livePrice, '0', self::SCALE) > 0;
        foreach ($tiers as $tier) {
            $target = $this->placement->targetPrice($tier);
            $isHit  = $hasLive && bccomp($livePrice, $target, self::SCALE) >= 0;

            // No ceiling known: only tiers whose target already passed can be handled.
            // Sorted by target, so the first out-of-range tier ends the coin.
            if (! $isHit && ($ceiling === null || $this->placement->exceedsCeiling($target, $ceiling))) {
                break;
            }
            if ($summary['attempts'] >= $budget) {
                break;
            }

            $result = $this->placement->tryPlace($tier, $livePrice, $ceiling);
            $summary['results'][] = $result;
            $summary['attempts']++;
            match ($result['outcome']) {
                'placed'    => $summary['placed']++,
                'completed' => $summary['completed']++,
                'waiting'   => $summary['rejected']++,
                default     => null,
            };
            if ($result['outcome'] === 'waiting' && str_contains((string) ($result['error'] ?? ''), 'PERCENT_PRICE')) {
                break;
            }
        }

        $handled = $summary['placed'] + $summary['completed'];
        $summary['still_waiting'] = $tiers->count() - $handled;

        BotSellOrder::whereIn('id', $tiers->pluck('id'))
            ->where('placement_status', BotSellOrder::PLACEMENT_WAITING)
            ->update(['last_placement_check_at' => now()]);

        return $summary;
    }

    /**
     * Admin "place this tier now": tries the exchange even above the estimated
     * ceiling (Binance decides); a rejection keeps the tier WAITING.
     *
     * @return array<string,mixed>
     */
    public function placeTier(BotSellOrder $tier): array
    {
        if ($tier->status !== BotSellOrder::STATUS_OPEN || $tier->placement_status !== BotSellOrder::PLACEMENT_WAITING) {
            return ['outcome' => 'skipped', 'sell_order_id' => $tier->id, 'error' => 'این پله در انتظار ثبت نیست.'];
        }

        $currency = $tier->botBuyExecution->currency;
        if ($reason = $this->blockReason($currency->id)) {
            return ['outcome' => 'skipped', 'sell_order_id' => $tier->id, 'error' => $reason];
        }

        return $this->placement->tryPlace($tier, $this->livePrice($currency), null);
    }

    /**
     * @return array<string,mixed>
     */
    public function details(Currency $currency): array
    {
        $symbol    = strtoupper((string) $currency->symbol);
        $livePrice = $this->livePrice($currency);
        $ceiling   = $this->placement->ceiling($symbol.'USDT', $livePrice);

        $tiers = $this->waitingTiers($currency->id)->map(function (BotSellOrder $t) use ($livePrice, $ceiling) {
            $target    = $this->placement->targetPrice($t);
            $execution = $t->botBuyExecution;
            $user      = $execution->botOrder?->user;

            return [
                'sell_order_id'           => $t->id,
                'bot_order_id'            => $execution->bot_order_id,
                'user_label'              => $user?->email ?: $user?->mobile ?: ($user ? '#'.$user->id : null),
                'target_type'             => $t->target_type,
                'target_value'            => (string) $t->target_value,
                'avg_buy_price'           => (string) $execution->avg_buy_price,
                'target_price'            => $target,
                'amount'                  => (string) $t->amount_to_sell,
                'share_percent'           => (string) $t->share_percent,
                'above_live_percent'      => $this->percentAbove($target, $livePrice),
                // How much the reference price must still rise before this tier fits under the ceiling.
                'rise_needed_percent'     => $ceiling !== null && $this->placement->exceedsCeiling($target, $ceiling)
                    ? $this->percentAbove($target, $ceiling['place_max_price'])
                    : null,
                'placeable_now'           => $ceiling !== null && ! $this->placement->exceedsCeiling($target, $ceiling),
                'waiting_since'           => $t->waiting_since?->toIso8601String(),
                'placement_attempts'      => (int) $t->placement_attempts,
                'last_placement_error'    => $t->last_placement_error,
                'last_placement_check_at' => $t->last_placement_check_at?->toIso8601String(),
            ];
        })->values();

        return [
            'currency'     => ['id' => $currency->id, 'symbol' => $symbol, 'name' => $currency->name],
            'live_price'   => $livePrice,
            'ceiling'      => $ceiling,
            'block_reason' => $this->blockReason($currency->id),
            'tiers'        => $tiers,
        ];
    }

    /**
     * @param array<string,mixed> $summary
     */
    public function rememberLastRun(array $summary): void
    {
        Cache::forever(self::LAST_RUN_CACHE_KEY, $summary);
    }

    public function lastRun(): ?array
    {
        return Cache::get(self::LAST_RUN_CACHE_KEY);
    }

    public function blockReason(int $currencyId): ?string
    {
        return $this->placement->blockReason($currencyId);
    }

    /**
     * @return Collection<int,BotSellOrder> sorted by absolute target price, lowest first
     */
    private function waitingTiers(int $currencyId): Collection
    {
        return BotSellOrder::query()
            ->where('status', BotSellOrder::STATUS_OPEN)
            ->where('placement_status', BotSellOrder::PLACEMENT_WAITING)
            ->whereHas('botBuyExecution', fn ($q) => $q->where('currency_id', $currencyId))
            ->with(['botBuyExecution.currency', 'botBuyExecution.botOrder.user'])
            ->orderBy('id')
            ->get()
            ->sort(fn (BotSellOrder $a, BotSellOrder $b) => bccomp(
                $this->placement->targetPrice($a),
                $this->placement->targetPrice($b),
                self::SCALE,
            ) ?: $a->id <=> $b->id)
            ->values();
    }

    private function livePrice(Currency $currency): string
    {
        try {
            $price = $this->priceFeed->getLive($currency->id);
        } catch (\Throwable $e) {
            Log::channel('smart-bot')->warning('bot.waiting.no_live_price', [
                'currency' => $currency->symbol,
                'error'    => $e->getMessage(),
            ]);

            return '0';
        }

        return $price > 0 ? number_format($price, self::SCALE, '.', '') : '0';
    }

    private function percentAbove(string $price, string $reference): ?string
    {
        if (bccomp($reference, '0', self::SCALE) <= 0 || bccomp($price, '0', self::SCALE) <= 0) {
            return null;
        }

        return bcmul(bcsub(bcdiv($price, $reference, self::SCALE), '1', self::SCALE), '100', 2);
    }
}

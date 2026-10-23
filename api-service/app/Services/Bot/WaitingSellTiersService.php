<?php

namespace App\Services\Bot;

use App\Models\Bot\BotGlobalSettings;
use App\Models\Bot\BotSellOrder;
use App\Models\Currency;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Drives WAITING sell tiers ({@see SellTierPlacementService}) onto the
 * exchange, per coin, lowest targets first:
 *   - tiers above Binance's price ceiling wait (waiting_reason = price_ceiling);
 *   - tiers that fit under the ceiling take the free MAX_NUM_ORDERS slots;
 *     when none is left, the farthest untouched bot order is rotated out so
 *     the nearer tier gets its slot, otherwise they wait (order_limit).
 * Used by bot:place-waiting-sells and the admin "waiting tiers" page, which
 * also gets a placement forecast per tier from details().
 */
class WaitingSellTiersService
{
    public const LAST_RUN_CACHE_KEY = 'bot:waiting-sells:last-run';

    /** Price rises (percent over live) the details forecast is summarized at. */
    private const MILESTONES = [0, 5, 10, 20, 30, 50, 75, 100, 150, 200];

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
     * Check one coin's WAITING tiers and place every one that fits, using at
     * most $budget placement orders and $rotationBudget rotations.
     *
     * @return array{currency_id:int, symbol:string, waiting:int, placed:int, completed:int, rejected:int, rotated:int, still_waiting:int, price_waiting:int, slot_waiting:int, attempts:int, live_price:?string, ceiling:?array, slots:?array, skipped:?string, results:list<array<string,mixed>>}
     */
    public function placeCurrency(Currency $currency, int $budget, int $rotationBudget = 0): array
    {
        $symbol  = strtoupper((string) $currency->symbol);
        $market  = $symbol.'USDT';
        $tiers   = $this->waitingTiers($currency->id);
        $summary = [
            'currency_id'   => $currency->id,
            'symbol'        => $symbol,
            'waiting'       => $tiers->count(),
            'placed'        => 0,
            'completed'     => 0,
            'rejected'      => 0,
            'rotated'       => 0,
            'still_waiting' => 0,
            'price_waiting' => 0,
            'slot_waiting'  => 0,
            'attempts'      => 0,
            'live_price'    => null,
            'ceiling'       => null,
            'slots'         => null,
            'skipped'       => null,
            'results'       => [],
        ];
        if ($tiers->isEmpty()) {
            return $summary;
        }

        $summary['skipped'] = $this->blockReason($currency->id);
        if ($summary['skipped'] !== null) {
            $summary['still_waiting'] = $tiers->count();

            return $summary + $this->reasonCounts($currency->id);
        }

        $livePrice  = $this->livePrice($currency);
        $ceiling    = $this->placement->ceiling($market, $livePrice);
        $openOrders = $this->placement->openOrders($market, fresh: true);
        $slots      = $this->placement->orderSlots($market, $openOrders);
        $summary['live_price'] = $livePrice;
        $summary['ceiling']    = $ceiling;
        $summary['slots']      = $slots;

        // Unknown slots (non-Binance driver / API down): only Binance's answer limits placement.
        $free          = $slots['free'] ?? null;
        $farthestFirst = $slots !== null ? array_reverse($this->placedTiers($currency->id)) : [];
        $settings      = BotGlobalSettings::current();
        $rotationsLeft = $settings->waiting_rotation_enabled ? $rotationBudget : 0;
        $rotationGap   = number_format((float) $settings->waiting_rotation_min_gap_percent, 4, '.', '');
        $hasLive       = bccomp($livePrice, '0', self::SCALE) > 0;
        $priceIds      = [];
        $slotIds       = [];
        $priceBlocked  = false;
        $slotsBlocked  = false;

        foreach ($tiers as $tier) {
            $target = $this->placement->targetPrice($tier);
            $isHit  = $hasLive && bccomp($livePrice, $target, self::SCALE) >= 0;

            // Sorted by target: once one tier is over the ceiling, every later one is too.
            if (! $isHit) {
                if ($ceiling === null) {
                    break;
                }
                if ($priceBlocked || $this->placement->exceedsCeiling($target, $ceiling)) {
                    $priceBlocked = true;
                    $priceIds[]   = $tier->id;
                    continue;
                }
                if ($slotsBlocked) {
                    $slotIds[] = $tier->id;
                    continue;
                }
            }
            if ($summary['attempts'] >= $budget) {
                break;
            }

            // Market sells need no slot; a limit sell needs one, rotated free if necessary.
            if (! $isHit && $free !== null && $free <= 0) {
                $rotation = $rotationsLeft > 0 ? $this->rotateFor($target, $market, $rotationGap, $farthestFirst, $openOrders) : null;
                if ($rotation !== null) {
                    $rotationsLeft--;
                    $summary['results'][] = $rotation;
                }
                if ($rotation === null || ! in_array($rotation['outcome'], ['rotated', 'filled'], true)) {
                    if (($rotation['outcome'] ?? null) === 'failed') {
                        $rotationsLeft = 0;
                    }
                    $slotsBlocked = true;
                    $slotIds[]    = $tier->id;
                    continue;
                }
                if ($rotation['outcome'] === 'rotated') {
                    $summary['rotated']++;
                }
                $free++;
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
            if ($result['outcome'] === 'placed' && $free !== null) {
                $free--;
            }
            if ($result['outcome'] === 'waiting' && ($result['waiting_reason'] ?? null) === BotSellOrder::WAITING_PRICE_CEILING) {
                $priceBlocked = true;
            }
            if ($result['outcome'] === 'waiting' && ($result['waiting_reason'] ?? null) === BotSellOrder::WAITING_ORDER_LIMIT) {
                $slotsBlocked = true;
                $free         = 0;
            }
        }

        $handled = $summary['placed'] + $summary['completed'];
        $summary['still_waiting'] = $tiers->count() - $handled;

        $this->setReason($priceIds, BotSellOrder::WAITING_PRICE_CEILING);
        $this->setReason($slotIds, BotSellOrder::WAITING_ORDER_LIMIT);
        BotSellOrder::whereIn('id', $tiers->pluck('id'))
            ->where('placement_status', BotSellOrder::PLACEMENT_WAITING)
            ->update(['last_placement_check_at' => now()]);

        return array_merge($summary, $this->reasonCounts($currency->id));
    }

    /**
     * Admin "place this tier now": tries the exchange even above the estimated
     * ceiling or without a free slot (Binance decides); a rejection keeps the
     * tier WAITING.
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
     * One coin's waiting tiers with, for each, the price at which it is
     * expected to reach Binance: the reference price its target needs to fit
     * under the ceiling, and the price at which enough lower bot tiers have
     * sold to leave it a slot (the bot keeps its `bot_capacity` lowest tiers
     * on the exchange). Assumes no new orders arrive meanwhile.
     *
     * @return array<string,mixed>
     */
    public function details(Currency $currency): array
    {
        $symbol    = strtoupper((string) $currency->symbol);
        $market    = $symbol.'USDT';
        $livePrice = $this->livePrice($currency);
        $settings  = BotGlobalSettings::current();
        $ceiling   = $this->placement->ceiling($market, $livePrice);
        $slots     = $this->placement->orderSlots($market, $this->placement->openOrders($market));
        $waiting   = $this->waitingTiers($currency->id);
        $placed    = $this->placedTiers($currency->id);

        if ($slots !== null) {
            $others = max(0, $slots['open'] - count($placed));
            $slots += [
                'bot_placed'   => count($placed),
                'others'       => $others,
                'bot_capacity' => max(0, $slots['max'] - $slots['reserve'] - $others),
            ];
        }

        $forecast = $this->forecast($waiting, $placed, $livePrice, $ceiling, $slots['bot_capacity'] ?? null);

        $tiers = $waiting->map(function (BotSellOrder $t) use ($livePrice, $ceiling, $forecast) {
            $target    = $this->placement->targetPrice($t);
            $execution = $t->botBuyExecution;
            $user      = $execution->botOrder?->user;
            $f         = $forecast[$t->id];

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
                'waiting_reason'          => $this->groupOf($t, $f['binding']),
                'waiting_since'           => $t->waiting_since?->toIso8601String(),
                'placement_attempts'      => (int) $t->placement_attempts,
                'last_placement_error'    => $t->last_placement_error,
                'last_placement_check_at' => $t->last_placement_check_at?->toIso8601String(),
            ] + $f;
        })->values();

        return [
            'currency'     => ['id' => $currency->id, 'symbol' => $symbol, 'name' => $currency->name],
            'live_price'   => $livePrice,
            'ceiling'      => $ceiling,
            'slots'        => $slots,
            'rotation'     => [
                'enabled'         => (bool) $settings->waiting_rotation_enabled,
                'min_gap_percent' => (float) $settings->waiting_rotation_min_gap_percent,
            ],
            'groups'       => $this->groups($tiers, $livePrice),
            'milestones'   => $this->milestones($tiers, $livePrice),
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
     * Rotate out the farthest bot order that sits at least $gapPercent above
     * $target and has nothing executed. Consumes $farthestFirst.
     *
     * @param  list<array{tier:BotSellOrder, target:string}>  $farthestFirst
     * @param  array<string,string>|null  $openOrders  orderId => executedQty
     * @return array<string,mixed>|null null when no order qualifies
     */
    private function rotateFor(string $target, string $market, string $gapPercent, array &$farthestFirst, ?array $openOrders): ?array
    {
        $minFar = bcmul($target, bcadd('1', bcdiv($gapPercent, '100', self::SCALE), self::SCALE), self::SCALE);

        while ($farthestFirst !== [] && bccomp($farthestFirst[0]['target'], $minFar, self::SCALE) > 0) {
            $far      = array_shift($farthestFirst);
            $executed = $openOrders[(string) $far['tier']->exchange_order_id] ?? null;
            // Not open any more (sync will settle it) or partly filled: never canceled here.
            if ($executed === null || bccomp($executed, '0', self::SCALE) > 0) {
                continue;
            }

            $result = $this->placement->rotateOut($far['tier'], $market) + [
                'action'       => 'rotate_out',
                'target_price' => $far['target'],
                'for_target'   => $target,
            ];
            if ($result['outcome'] === 'skipped') {
                continue;
            }
            if ($result['outcome'] === 'failed') {
                Log::channel('smart-bot')->critical('bot.waiting.rotation_failed', [
                    'market'        => $market,
                    'sell_order_id' => $result['sell_order_id'],
                    'order_id'      => $result['exchange_order_id'] ?? null,
                    'error'         => $result['error'] ?? null,
                ]);
            } else {
                Log::channel('smart-bot')->info('bot.waiting.rotated', [
                    'market'        => $market,
                    'sell_order_id' => $result['sell_order_id'],
                    'outcome'       => $result['outcome'],
                    'far_target'    => $far['target'],
                    'for_target'    => $target,
                ]);
            }

            return $result;
        }

        return null;
    }

    /**
     * @param  Collection<int,BotSellOrder>  $waiting  sorted by target
     * @param  list<array{tier:BotSellOrder, target:string}>  $placed  sorted by target
     * @return array<int,array{queue_rank:int, lower_to_sell:int, slot_price:?string, ceiling_price:?string, forecast_price:?string, forecast_rise_percent:?string, binding:string}>
     *         binding: hit | now | ceiling | slot | unknown
     */
    private function forecast(Collection $waiting, array $placed, string $livePrice, ?array $ceiling, ?int $capacity): array
    {
        $combined = [];
        foreach ($placed as $p) {
            $combined[] = ['target' => $p['target'], 'waiting_id' => null, 'id' => $p['tier']->id];
        }
        foreach ($waiting as $w) {
            $combined[] = ['target' => $this->placement->targetPrice($w), 'waiting_id' => $w->id, 'id' => $w->id];
        }
        usort($combined, fn (array $a, array $b) => bccomp($a['target'], $b['target'], self::SCALE) ?: $a['id'] <=> $b['id']);

        $hasLive = bccomp($livePrice, '0', self::SCALE) > 0;
        $out     = [];
        foreach ($combined as $i => $row) {
            if ($row['waiting_id'] === null) {
                continue;
            }
            $rank   = $i + 1;
            $target = $row['target'];

            $lower     = $capacity !== null ? max(0, $rank - $capacity) : 0;
            $slotPrice = $lower > 0 ? $combined[$lower - 1]['target'] : null;

            $ceilingPrice = $ceiling !== null && $this->placement->exceedsCeiling($target, $ceiling)
                ? bcdiv(bcmul($target, $ceiling['reference_price'], self::SCALE), $ceiling['place_max_price'], self::SCALE)
                : null;

            [$price, $binding] = match (true) {
                $hasLive && bccomp($livePrice, $target, self::SCALE) >= 0 => [null, 'hit'],
                // The cycle places nothing but passed targets without a known ceiling.
                $ceiling === null   => [null, 'unknown'],
                $ceilingPrice !== null && ($slotPrice === null || bccomp($ceilingPrice, $slotPrice, self::SCALE) >= 0) => [$ceilingPrice, 'ceiling'],
                $slotPrice !== null => [$slotPrice, 'slot'],
                default             => [null, 'now'],
            };

            $out[$row['waiting_id']] = [
                'queue_rank'            => $rank,
                'lower_to_sell'         => $lower,
                'slot_price'            => $slotPrice,
                'ceiling_price'         => $ceilingPrice,
                'forecast_price'        => $price,
                'forecast_rise_percent' => $price !== null ? $this->percentAbove($price, $livePrice) : null,
                'binding'               => $binding,
            ];
        }

        return $out;
    }

    /**
     * Per-reason totals and the price range over which each group is expected to be placed.
     *
     * @param  Collection<int,array<string,mixed>>  $tiers
     * @return array<string,array<string,mixed>>
     */
    private function groups(Collection $tiers, string $livePrice): array
    {
        $groups = [];
        foreach ([BotSellOrder::WAITING_PRICE_CEILING, BotSellOrder::WAITING_ORDER_LIMIT] as $reason) {
            $rows   = $tiers->where('waiting_reason', $reason);
            $prices = $rows->pluck('forecast_price')->filter()->sort(fn ($a, $b) => bccomp($a, $b, self::SCALE))->values();
            $first  = $prices->first();
            $last   = $prices->last();

            $groups[$reason] = [
                'count'             => $rows->count(),
                'amount'            => $rows->reduce(fn (string $sum, array $r) => bcadd($sum, $this->num($r['amount']), self::SCALE), '0'),
                'placeable_now'     => $rows->whereIn('binding', ['now', 'hit'])->count(),
                'by_ceiling'        => $rows->where('binding', 'ceiling')->count(),
                'by_slot'           => $rows->where('binding', 'slot')->count(),
                'first_price'       => $first,
                'first_rise'        => $first !== null ? $this->percentAbove($first, $livePrice) : null,
                'last_price'        => $last,
                'last_rise'         => $last !== null ? $this->percentAbove($last, $livePrice) : null,
                'max_lower_to_sell' => (int) $rows->max('lower_to_sell'),
            ];
        }

        return $groups;
    }

    /**
     * How many waiting tiers are expected on Binance (or sold) if the price
     * rises by each milestone percent.
     *
     * @param  Collection<int,array<string,mixed>>  $tiers
     * @return list<array<string,mixed>>
     */
    private function milestones(Collection $tiers, string $livePrice): array
    {
        if ($tiers->isEmpty() || bccomp($livePrice, '0', self::SCALE) <= 0) {
            return [];
        }

        $rows = [];
        foreach (self::MILESTONES as $pct) {
            $price  = bcmul($livePrice, bcadd('1', bcdiv((string) $pct, '100', self::SCALE), self::SCALE), self::SCALE);
            $placed = $tiers->filter(fn (array $t) => in_array($t['binding'], ['now', 'hit'], true)
                || ($t['forecast_price'] !== null && bccomp($t['forecast_price'], $price, self::SCALE) <= 0));

            $rows[] = [
                'rise_percent'  => $pct,
                'price'         => $price,
                'price_ceiling' => $placed->where('waiting_reason', BotSellOrder::WAITING_PRICE_CEILING)->count(),
                'order_limit'   => $placed->where('waiting_reason', BotSellOrder::WAITING_ORDER_LIMIT)->count(),
                'total'         => $placed->count(),
                'remaining'     => $tiers->count() - $placed->count(),
            ];
            if ($placed->count() === $tiers->count()) {
                break;
            }
        }

        return $rows;
    }

    private function groupOf(BotSellOrder $tier, string $binding): string
    {
        return $tier->waiting_reason
            ?? ($binding === 'slot' ? BotSellOrder::WAITING_ORDER_LIMIT : BotSellOrder::WAITING_PRICE_CEILING);
    }

    /**
     * @param list<int> $ids
     */
    private function setReason(array $ids, string $reason): void
    {
        foreach (array_chunk($ids, 500) as $chunk) {
            BotSellOrder::whereIn('id', $chunk)
                ->where('placement_status', BotSellOrder::PLACEMENT_WAITING)
                ->update(['waiting_reason' => $reason]);
        }
    }

    /**
     * @return array{price_waiting:int, slot_waiting:int}
     */
    private function reasonCounts(int $currencyId): array
    {
        $counts = BotSellOrder::query()
            ->where('status', BotSellOrder::STATUS_OPEN)
            ->where('placement_status', BotSellOrder::PLACEMENT_WAITING)
            ->whereHas('botBuyExecution', fn ($q) => $q->where('currency_id', $currencyId))
            ->selectRaw('waiting_reason, COUNT(*) as aggregate')
            ->groupBy('waiting_reason')
            ->pluck('aggregate', 'waiting_reason');

        $slot = (int) ($counts[BotSellOrder::WAITING_ORDER_LIMIT] ?? 0);

        return [
            'price_waiting' => (int) $counts->sum() - $slot,
            'slot_waiting'  => $slot,
        ];
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

    /**
     * The coin's bot limit sells currently on the exchange, each holding one slot.
     *
     * @return list<array{tier:BotSellOrder, target:string}> sorted by target, lowest first
     */
    private function placedTiers(int $currencyId): array
    {
        return BotSellOrder::query()
            ->where('status', BotSellOrder::STATUS_OPEN)
            ->where('placement_status', BotSellOrder::PLACEMENT_PLACED)
            ->where('exchange', $this->placement->exchangeName())
            ->whereNotNull('exchange_order_id')
            ->whereHas('botBuyExecution', fn ($q) => $q->where('currency_id', $currencyId))
            ->with('botBuyExecution')
            ->orderBy('id')
            ->get()
            ->map(fn (BotSellOrder $t) => ['tier' => $t, 'target' => $this->placement->targetPrice($t)])
            ->sort(fn (array $a, array $b) => bccomp($a['target'], $b['target'], self::SCALE) ?: $a['tier']->id <=> $b['tier']->id)
            ->values()
            ->all();
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

    private function num(mixed $v): string
    {
        $s = trim((string) $v);

        return is_numeric($s) && stripos($s, 'e') === false ? $s : number_format((float) $s, self::SCALE, '.', '');
    }
}

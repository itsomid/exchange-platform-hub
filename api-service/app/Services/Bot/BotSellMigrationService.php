<?php

namespace App\Services\Bot;

use App\Events\Bot\BotSellOrderFilled;
use App\Jobs\Bot\MigrateSellOrdersJob;
use App\Jobs\Bot\OpenSellOrdersJob;
use App\Models\Bot\BotBuyExecution;
use App\Models\Bot\BotCurrencyCancellation;
use App\Models\Bot\BotGlobalSettings;
use App\Models\Bot\BotSellMigration;
use App\Models\Bot\BotSellOrder;
use App\Models\Currency;
use App\Services\Bot\ReferenceExchange\ExchangeContract;
use App\Services\Bot\ReferenceExchange\ExchangeOrderResult;
use App\Services\Bot\ReferenceExchange\ExchangePositionCloser;
use App\Services\Exchanges\Asset\AssetFactory;
use App\Services\Exchanges\Asset\Binance\BinanceOrderFormatter;
use App\Services\Exchanges\Asset\Binance\BinanceRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Reference-exchange switch: the bot's limit sells were canceled by hand on
 * CoinEx and the coin moved to Binance. A run re-places one coin's OPEN CoinEx
 * tiers on Binance by editing the same bot_sell_orders rows in place:
 *   - target not reached yet → LIMIT SELL at the tier's target price;
 *   - target already reached → MARKET SELL, then the regular fill path
 *     (BotSellOrderFilled → settlement + reinvest) at the real fill.
 * Tiers below Binance's minimum order size are merged into an adjacent tier of
 * the same buy; whatever is still too small is sold in one aggregated market
 * order and settled with the currency-cancel refund rule.
 * FILLED / CANCELED tiers are never touched. A run refuses to start when the
 * coin's free Binance balance cannot cover every remaining tier.
 */
class BotSellMigrationService
{
    private const SCALE = 8;

    public function __construct(
        private readonly ExchangeContract $exchange,
        private readonly ExchangePositionCloser $closer,
        private readonly SettlementService $settlement,
        private readonly PriceFeed $priceFeed,
        private readonly BotOrderDescriptionService $descriptions,
        private readonly SellTierPlacementService $placement,
    ) {}

    /**
     * @return array<string,mixed>
     */
    public function preview(Currency $currency): array
    {
        $symbol    = strtoupper((string) $currency->symbol);
        $market    = $symbol.'USDT';
        $livePrice = $this->livePrice($currency);
        $filters   = $this->filters($market);
        $sells     = $this->pendingSells($currency->id);
        $balance   = $this->binanceBalance($symbol);
        $perfPct   = $this->num(BotGlobalSettings::current()->performance_fee_percent);

        $priceLimit = $filters !== null ? $this->placement->ceiling($market, $livePrice) : null;
        $plan       = $this->plan($sells, $livePrice, $filters, $priceLimit);
        $tiers      = [];
        $totals     = [
            'tiers'              => 0,
            'limit_tiers'        => 0,
            'market_tiers'       => 0,
            'merge_tiers'        => 0,
            'close_tiers'        => 0,
            'wait_tiers'         => 0,
            'limit_amount'       => '0',
            'market_amount'      => '0',
            'merge_amount'       => '0',
            'close_amount'       => '0',
            'wait_amount'        => '0',
        ];
        $required = '0';
        $users    = [];
        $orders   = [];

        foreach ($sells as $sell) {
            $execution = $sell->botBuyExecution;
            $amount    = $this->num($sell->amount_to_sell);
            $step      = $plan[$sell->id];
            $action    = $step['action'];
            $estimate  = $action === 'close' && bccomp($livePrice, '0', self::SCALE) > 0
                ? $this->settlement->currencyCancelBreakdown($execution, $amount, $livePrice, $perfPct)
                : null;
            $overMax = $action === 'wait'
                && bccomp($step['target_price'], $priceLimit['max_price'], self::SCALE) > 0;

            $tiers[] = [
                'sell_order_id'     => $sell->id,
                'bot_order_id'      => $execution->bot_order_id,
                'user_id'           => $execution->botOrder?->user_id,
                'user_email'        => $execution->botOrder?->user?->email,
                'user_mobile'       => $execution->botOrder?->user?->mobile,
                'exchange_order_id' => $sell->exchange_order_id,
                'target_type'       => $sell->target_type,
                'target_value'      => $this->num($sell->target_value),
                'share_percent'     => $this->num($sell->share_percent),
                'avg_buy_price'     => $this->num($execution->avg_buy_price),
                'target_price'      => $step['target_price'],
                'amount'            => $amount,
                'action'            => $action,
                'merge_into'        => $step['merge_into'],
                'merged_amount'     => $step['merged_amount'],
                'merged_share'      => $step['merged_share'],
                'binance_quantity'  => $step['quantity'],
                'estimated_refund'  => $estimate['refund'] ?? null,
                'estimated_net_pnl' => $estimate['net_pnl'] ?? null,
                'above_live_percent'   => $this->percentAbove($step['target_price'], $livePrice),
                'price_limit_exceeded' => $action === 'wait',
                'over_max_percent'     => $overMax ? $this->percentAbove($step['target_price'], $priceLimit['max_price']) : null,
            ];

            $totals['tiers']++;
            $totals[$action.'_tiers']++;
            $totals[$action.'_amount'] = bcadd($totals[$action.'_amount'], $amount, self::SCALE);
            $required = bcadd($required, $amount, self::SCALE);
            $users[$execution->botOrder?->user_id]  = true;
            $orders[$execution->bot_order_id]       = true;
        }

        $available = $balance['available'] ?? null;
        $shortfall = $available !== null && bccomp($available, $required, self::SCALE) < 0
            ? bcsub($required, $available, self::SCALE)
            : '0';

        $runningMigration    = $this->runningMigration($currency->id);
        $runningCancellation = $this->runningCancellation($currency->id);

        $blockReason = match (true) {
            $totals['tiers'] === 0                => 'برای این ارز پله فروش بازی که در CoinEx ثبت شده باشد وجود ندارد.',
            $runningMigration !== null            => "انتقال دیگری برای این ارز (#{$runningMigration->id}) در حال اجراست.",
            $runningCancellation !== null         => "لغو ارز (#{$runningCancellation->id}) برای این ارز در حال اجراست.",
            bccomp($livePrice, '0', self::SCALE) <= 0 => "قیمت لحظه‌ای {$symbol} در دسترس نیست.",
            $balance === null                     => 'امکان دریافت موجودی از Binance نیست.',
            bccomp($shortfall, '0', self::SCALE) > 0 => "موجودی {$symbol} در Binance کافی نیست؛ کمبود: {$shortfall}",
            default                               => null,
        };

        return [
            'currency' => [
                'id'     => $currency->id,
                'symbol' => $symbol,
                'name'   => $currency->name,
            ],
            'market'           => $market,
            'live_price'       => $livePrice,
            'performance_fee_percent' => $perfPct,
            'balance'          => $balance,
            'required_amount'  => $required,
            'shortfall'        => $shortfall,
            'sufficient'       => $balance !== null && bccomp($shortfall, '0', self::SCALE) === 0,
            'filled_tiers'     => $this->filledTiersCount($currency->id),
            'totals'           => $totals + [
                'users'  => count(array_filter(array_keys($users))),
                'orders' => count($orders),
            ],
            'tiers'            => $tiers,
            'close'            => $this->closePreview($totals['close_amount'], $livePrice, $filters),
            'price_limit'      => $priceLimit,
            'can_start'        => $blockReason === null,
            'block_reason'     => $blockReason,
        ];
    }

    /**
     * @throws \DomainException with an admin-facing message when the run cannot start.
     */
    public function start(Currency $currency, ?int $adminId, ?string $adminLabel): BotSellMigration
    {
        $preview = $this->preview($currency);
        if (! $preview['can_start']) {
            throw new \DomainException($preview['block_reason']);
        }

        $migration = DB::transaction(function () use ($currency, $preview, $adminId, $adminLabel) {
            Currency::whereKey($currency->id)->lockForUpdate()->first();

            if ($running = $this->runningMigration($currency->id)) {
                throw new \DomainException("انتقال دیگری برای این ارز (#{$running->id}) در حال اجراست.");
            }
            if ($running = $this->runningCancellation($currency->id)) {
                throw new \DomainException("لغو ارز (#{$running->id}) برای این ارز در حال اجراست.");
            }

            return BotSellMigration::create([
                'currency_id'     => $currency->id,
                'admin_id'        => $adminId,
                'admin_label'     => $adminLabel,
                'status'          => BotSellMigration::STATUS_PENDING,
                'tiers_count'     => $preview['totals']['tiers'],
                'required_amount' => $preview['required_amount'],
            ]);
        });

        Log::channel('smart-bot')->info('bot.sell_migration.queued', [
            'migration_id' => $migration->id,
            'currency'     => $currency->symbol,
            'tiers'        => $preview['totals']['tiers'],
            'admin_id'     => $adminId,
        ]);

        MigrateSellOrdersJob::dispatch($migration->id)->onQueue('bot-settlement');

        return $migration->fresh();
    }

    public function run(int $migrationId): void
    {
        $claimed = BotSellMigration::whereKey($migrationId)
            ->where('status', BotSellMigration::STATUS_PENDING)
            ->update(['status' => BotSellMigration::STATUS_RUNNING, 'started_at' => now()]);
        if (! $claimed) {
            return;
        }

        $migration = BotSellMigration::with('currency')->findOrFail($migrationId);

        try {
            $this->execute($migration);
        } catch (\Throwable $e) {
            Log::channel('smart-bot')->error('bot.sell_migration.exception', [
                'migration_id' => $migrationId,
                'exception'    => $e::class,
                'message'      => $e->getMessage(),
                'file'         => $e->getFile().':'.$e->getLine(),
            ]);

            $migration->update([
                'status'      => BotSellMigration::STATUS_FAILED,
                'error'       => $e->getMessage(),
                'finished_at' => now(),
            ]);
        }
    }

    private function execute(BotSellMigration $migration): void
    {
        $currency = $migration->currency;
        $symbol   = strtoupper((string) $currency->symbol);
        $market   = $symbol.'USDT';

        $livePrice = $this->livePrice($currency);
        if (bccomp($livePrice, '0', self::SCALE) <= 0) {
            throw new \RuntimeException("قیمت لحظه‌ای {$symbol} در دسترس نیست؛ انتقال انجام نشد.");
        }

        $sells    = $this->pendingSells($currency->id);
        $required = $sells->reduce(fn (string $sum, BotSellOrder $s) => bcadd($sum, $this->num($s->amount_to_sell), self::SCALE), '0');
        $balance  = $this->binanceBalance($symbol);
        if ($balance === null) {
            throw new \RuntimeException('امکان دریافت موجودی از Binance نیست؛ انتقال انجام نشد.');
        }
        if (bccomp($balance['available'], $required, self::SCALE) < 0) {
            throw new \RuntimeException(sprintf(
                'موجودی %s در Binance کافی نیست (موجود: %s، موردنیاز: %s)؛ انتقال انجام نشد.',
                $symbol, $balance['available'], $required,
            ));
        }

        $filters    = $this->filters($market);
        $ceiling    = $filters !== null ? $this->placement->ceiling($market, $livePrice) : null;
        $plan       = $this->plan($sells, $livePrice, $filters, $ceiling);
        $results    = [];
        $counts     = ['placed' => 0, 'completed' => 0, 'merged' => 0, 'closed' => 0, 'waiting' => 0, 'failed' => 0];
        $executions = [];
        $record     = function (BotSellOrder $sell, array $result) use (&$results, &$counts, &$executions) {
            $results[] = $result;
            $counts[$result['outcome']]++;
            if ($result['outcome'] !== 'failed') {
                $executions[$sell->bot_buy_execution_id] = $sell->botBuyExecution;
            }
        };

        // 1) Fold below-minimum tiers into their surviving sibling tier.
        $absorbedBy = [];
        foreach ($sells as $sell) {
            if ($plan[$sell->id]['action'] === 'merge') {
                $absorbedBy[$plan[$sell->id]['merge_into']][] = $sell->id;
            }
        }
        $blocked = [];
        foreach ($absorbedBy as $survivorId => $absorbedIds) {
            $error = $this->mergeTiers($survivorId, $absorbedIds);
            if ($error !== null) {
                $blocked[$survivorId] = true;
            }
            foreach ($absorbedIds as $id) {
                $sell = $sells->firstWhere('id', $id);
                $record($sell, [
                    'sell_order_id'         => $id,
                    'old_exchange_order_id' => $sell->exchange_order_id,
                    'action'                => 'merge',
                    'merge_into'            => $survivorId,
                    'amount'                => $this->num($sell->amount_to_sell),
                    'outcome'               => $error === null ? 'merged' : 'failed',
                ] + ($error === null ? [] : ['error' => $error]));
            }
        }

        // 2) Re-place every surviving tier on the reference exchange.
        $toClose = [];
        foreach ($sells as $sell) {
            $action = $plan[$sell->id]['action'];
            if ($action === 'merge') {
                continue;
            }
            if ($action === 'close') {
                $toClose[] = $sell;
                continue;
            }
            if (isset($blocked[$sell->id])) {
                $record($sell, [
                    'sell_order_id' => $sell->id,
                    'action'        => $action,
                    'outcome'       => 'failed',
                    'error'         => 'merge of sibling tiers failed',
                ]);
                continue;
            }

            $sell->refresh();
            $record($sell, $action === 'wait'
                ? $this->waitTier($sell, $plan[$sell->id]['target_price'], $ceiling)
                : $this->migrateTier($sell, $market, $livePrice, $migration->id));
        }

        // 3) Tiers that are still too small: one aggregated market sell, then settle.
        $close = null;
        if ($toClose !== []) {
            [$close, $closeResults] = $this->closeTiers($toClose, $market, $livePrice, $filters, $migration->id);
            foreach ($toClose as $sell) {
                $record($sell, $closeResults[$sell->id]);
            }
        }

        foreach ($executions as $execution) {
            $this->descriptions->appendSystemNote($execution, sprintf(
                'انتقال پله‌های فروش از CoinEx به Binance (#%d)',
                $migration->id,
            ));
        }

        $migration->update([
            'status'          => BotSellMigration::STATUS_DONE,
            'tiers_count'     => $sells->count(),
            'placed_count'    => $counts['placed'],
            'completed_count' => $counts['completed'],
            'merged_count'    => $counts['merged'],
            'closed_count'    => $counts['closed'],
            'waiting_count'   => $counts['waiting'],
            'failed_count'    => $counts['failed'],
            'required_amount' => $required,
            'details'         => ['live_price' => $livePrice, 'tiers' => $results, 'close' => $close, 'ceiling' => $ceiling],
            'finished_at'     => now(),
        ]);

        Log::channel('smart-bot')->info('bot.sell_migration.done', [
            'migration_id' => $migration->id,
            'currency'     => $symbol,
            'live_price'   => $livePrice,
        ] + $counts);
    }

    /**
     * Re-place one tier on the reference exchange. The row stays locked while
     * the order is placed so no cancel/settle path can act on its stale id.
     *
     * @return array<string,mixed>
     */
    private function migrateTier(BotSellOrder $sell, string $market, string $livePrice, int $migrationId): array
    {
        $targetPrice = $this->targetPrice($sell);
        $isHit       = $this->isHit($livePrice, $targetPrice);
        $base        = [
            'sell_order_id'         => $sell->id,
            'old_exchange_order_id' => $sell->exchange_order_id,
            'action'                => $isHit ? 'market' : 'limit',
            'target_price'          => $targetPrice,
            'amount'                => $this->num($sell->amount_to_sell),
        ];

        try {
            /** @var array{0:?BotSellOrder,1:?ExchangeOrderResult} $outcome */
            $outcome = DB::transaction(function () use ($sell, $market, $targetPrice, $isHit) {
                $fresh = BotSellOrder::whereKey($sell->id)->lockForUpdate()->first();
                if (! $fresh || $fresh->status !== BotSellOrder::STATUS_OPEN || $fresh->exchange !== BotSellOrder::EXCHANGE_COINEX) {
                    return [null, null];
                }

                $amount = $this->num($fresh->amount_to_sell);
                $res    = $isHit
                    ? $this->exchange->placeMarketSell($market, $amount)
                    : $this->exchange->placeLimitSell($market, $amount, $targetPrice);

                if (! $isHit && $res->exchangeOrderId === null && $this->placement->isPriceRangeRejection($res)) {
                    $fresh->update([
                        'previous_exchange_order_id' => $fresh->exchange_order_id,
                        'migrated_at'                => now(),
                    ] + $this->placement->waitingAttributes(SellTierPlacementService::errorText($res)));

                    return [$fresh, $res];
                }

                if ($res->exchangeOrderId === null || ($isHit && bccomp($this->num($res->filledAmount), '0', self::SCALE) <= 0)) {
                    return [$fresh, $res];
                }

                $fresh->update(array_filter([
                    'previous_exchange_order_id' => $fresh->exchange_order_id,
                    'exchange_order_id'          => $res->exchangeOrderId,
                    'exchange'                   => $this->exchange->name(),
                    'migrated_at'                => now(),
                    'sell_ref_exchange_fee'      => $isHit ? ($res->exchangeFee ?? '0') : null,
                ], fn ($v) => $v !== null));

                return [$fresh, $res];
            });
        } catch (\Throwable $e) {
            Log::channel('smart-bot')->error('bot.sell_migration.tier_exception', [
                'migration_id'  => $migrationId,
                'sell_order_id' => $sell->id,
                'error'         => $e->getMessage(),
            ]);

            return $base + ['outcome' => 'failed', 'error' => $e->getMessage()];
        }

        [$fresh, $res] = $outcome;

        if ($fresh === null) {
            return $base + ['outcome' => 'failed', 'error' => 'tier is no longer OPEN on CoinEx'];
        }

        if ($fresh->placement_status === BotSellOrder::PLACEMENT_WAITING) {
            return $base + ['action' => 'wait', 'outcome' => 'waiting', 'error' => SellTierPlacementService::errorText($res)];
        }

        if ($fresh->exchange === BotSellOrder::EXCHANGE_COINEX) {
            $error = trim(($res->errorCode ?? '').' '.($res->errorMessage ?? '')) ?: 'order not placed';
            Log::channel('smart-bot')->warning('bot.sell_migration.place_failed', [
                'migration_id'  => $migrationId,
                'sell_order_id' => $sell->id,
                'market'        => $market,
                'action'        => $base['action'],
                'error'         => $error,
            ]);

            return $base + ['outcome' => 'failed', 'error' => $error];
        }

        if (! $isHit) {
            return $base + ['outcome' => 'placed', 'new_exchange_order_id' => $res->exchangeOrderId];
        }

        BotSellOrderFilled::dispatch(
            $fresh,
            $this->num($res->filledAmount),
            $this->num($res->avgPrice),
            '0',
            $res->exchangeFee ?? '0',
        );

        return $base + [
            'outcome'               => 'completed',
            'new_exchange_order_id' => $res->exchangeOrderId,
            'filled_amount'         => $this->num($res->filledAmount),
            'avg_price'             => $this->num($res->avgPrice),
            'exchange_fee'          => $this->num($res->exchangeFee),
        ];
    }

    /**
     * Tier priced above Binance's ceiling: leave it OPEN as WAITING (moved off
     * CoinEx) for bot:place-waiting-sells instead of sending a doomed order.
     *
     * @return array<string,mixed>
     */
    private function waitTier(BotSellOrder $sell, string $targetPrice, ?array $ceiling): array
    {
        $reason = sprintf('price %s above Binance ceiling %s', $targetPrice, $ceiling['place_max_price'] ?? '?');
        $base   = [
            'sell_order_id'         => $sell->id,
            'old_exchange_order_id' => $sell->exchange_order_id,
            'action'                => 'wait',
            'target_price'          => $targetPrice,
            'amount'                => $this->num($sell->amount_to_sell),
        ];

        $updated = DB::transaction(function () use ($sell, $reason) {
            $fresh = BotSellOrder::whereKey($sell->id)->lockForUpdate()->first();
            if (! $fresh || $fresh->status !== BotSellOrder::STATUS_OPEN || $fresh->exchange !== BotSellOrder::EXCHANGE_COINEX) {
                return false;
            }
            $fresh->update([
                'previous_exchange_order_id' => $fresh->exchange_order_id,
                'migrated_at'                => now(),
            ] + $this->placement->waitingAttributes($reason));

            return true;
        });

        return $updated
            ? $base + ['outcome' => 'waiting']
            : $base + ['outcome' => 'failed', 'error' => 'tier is no longer OPEN on CoinEx'];
    }

    /**
     * Decide what the run does with each OPEN CoinEx tier. Per buy execution,
     * the smallest below-minimum tier is merged into its smaller adjacent tier
     * (the lower-target one survives with summed amount/share, as in
     * TargetCollapseService) until every tier clears the minimum or one is
     * left; tiers still below the minimum are closed. Limit tiers above
     * Binance's sell-price ceiling wait. Without Binance filters nothing is
     * merged, closed or kept waiting.
     *
     * @param  Collection<int,BotSellOrder>  $sells
     * @param  array<string,string>|null  $filters
     * @param  array<string,mixed>|null  $ceiling  {@see SellTierPlacementService::ceiling()}
     * @return array<int,array{action:string, merge_into:?int, target_price:string, merged_amount:?string, merged_share:?string, quantity:?string}>
     */
    private function plan(Collection $sells, string $livePrice, ?array $filters, ?array $ceiling = null): array
    {
        $plan = [];
        foreach ($sells->groupBy('bot_buy_execution_id') as $group) {
            $tiers = $group->map(fn (BotSellOrder $s) => [
                'id'           => $s->id,
                'target_price' => $this->targetPrice($s),
                'amount'       => $this->num($s->amount_to_sell),
                'share'        => $this->num($s->share_percent),
            ])->values()->all();
            usort($tiers, fn (array $a, array $b) => bccomp($a['target_price'], $b['target_price'], self::SCALE) ?: $a['id'] <=> $b['id']);
            $targets = array_column($tiers, 'target_price', 'id');

            $absorbed = [];
            while (count($tiers) > 1 && ($i = $this->smallestBelowMin($tiers, $livePrice, $filters)) !== null) {
                $last = count($tiers) - 1;
                $j    = match (true) {
                    $i === 0     => 1,
                    $i === $last => $i - 1,
                    default      => bccomp($tiers[$i - 1]['amount'], $tiers[$i + 1]['amount'], self::SCALE) <= 0 ? $i - 1 : $i + 1,
                };
                $keep   = min($i, $j);
                $drop   = max($i, $j);
                $keepId = $tiers[$keep]['id'];
                $dropId = $tiers[$drop]['id'];

                $tiers[$keep]['amount'] = bcadd($tiers[$keep]['amount'], $tiers[$drop]['amount'], self::SCALE);
                $tiers[$keep]['share']  = bcadd($tiers[$keep]['share'], $tiers[$drop]['share'], self::SCALE);
                foreach ($absorbed as $id => $into) {
                    if ($into === $dropId) {
                        $absorbed[$id] = $keepId;
                    }
                }
                $absorbed[$dropId] = $keepId;
                array_splice($tiers, $drop, 1);
            }

            foreach ($tiers as $t) {
                $eval      = $this->evaluate($t, $livePrice, $filters);
                $wasMerged = in_array($t['id'], $absorbed, true);
                $plan[$t['id']] = [
                    'action'        => match (true) {
                        $eval['below_min'] => 'close',
                        $eval['hit']       => 'market',
                        $this->placement->exceedsCeiling($t['target_price'], $ceiling) => 'wait',
                        default            => 'limit',
                    },
                    'merge_into'    => null,
                    'target_price'  => $t['target_price'],
                    'merged_amount' => $wasMerged ? $t['amount'] : null,
                    'merged_share'  => $wasMerged ? $t['share'] : null,
                    'quantity'      => $eval['quantity'],
                ];
            }
            foreach ($absorbed as $id => $into) {
                $plan[$id] = [
                    'action'        => 'merge',
                    'merge_into'    => $into,
                    'target_price'  => $targets[$id],
                    'merged_amount' => null,
                    'merged_share'  => null,
                    'quantity'      => null,
                ];
            }
        }

        return $plan;
    }

    /**
     * @param  list<array{id:int, target_price:string, amount:string, share:string}>  $tiers
     * @param  array<string,string>|null  $filters
     */
    private function smallestBelowMin(array $tiers, string $livePrice, ?array $filters): ?int
    {
        $found = null;
        foreach ($tiers as $i => $t) {
            if (! $this->evaluate($t, $livePrice, $filters)['below_min']) {
                continue;
            }
            if ($found === null || bccomp($t['amount'], $tiers[$found]['amount'], self::SCALE) < 0) {
                $found = $i;
            }
        }

        return $found;
    }

    /**
     * @param  array{target_price:string, amount:string}  $tier
     * @param  array<string,string>|null  $filters
     * @return array{hit:bool, quantity:string, below_min:bool}
     */
    private function evaluate(array $tier, string $livePrice, ?array $filters): array
    {
        $hit = $this->isHit($livePrice, $tier['target_price']);
        if ($filters === null) {
            return ['hit' => $hit, 'quantity' => $tier['amount'], 'below_min' => false];
        }

        $type     = $hit ? 'MARKET' : 'LIMIT';
        $quantity = BinanceOrderFormatter::formatQuantity($tier['amount'], BinanceOrderFormatter::stepFor($type, $filters));

        return [
            'hit'       => $hit,
            'quantity'  => $quantity,
            'below_min' => $this->isBelowMin($quantity, $hit ? $livePrice : $tier['target_price'], $type, $filters),
        ];
    }

    /**
     * Fold the absorbed tiers into the surviving one; absorbed rows are
     * canceled without any settlement since their coin now sells via the survivor.
     *
     * @param  list<int>  $absorbedIds
     * @return string|null error, or null on success
     */
    private function mergeTiers(int $survivorId, array $absorbedIds): ?string
    {
        try {
            return DB::transaction(function () use ($survivorId, $absorbedIds) {
                $rows = BotSellOrder::whereKey([$survivorId, ...$absorbedIds])->lockForUpdate()->get()->keyBy('id');
                foreach ([$survivorId, ...$absorbedIds] as $id) {
                    $row = $rows->get($id);
                    if (! $row || $row->status !== BotSellOrder::STATUS_OPEN || $row->exchange !== BotSellOrder::EXCHANGE_COINEX) {
                        return "tier #{$id} is no longer OPEN on CoinEx";
                    }
                }

                $survivor = $rows->get($survivorId);
                $amount   = $this->num($survivor->amount_to_sell);
                $share    = $this->num($survivor->share_percent);
                foreach ($absorbedIds as $id) {
                    $row    = $rows->get($id);
                    $amount = bcadd($amount, $this->num($row->amount_to_sell), self::SCALE);
                    $share  = bcadd($share, $this->num($row->share_percent), self::SCALE);
                    $row->update([
                        'status'        => BotSellOrder::STATUS_CANCELED,
                        'cancel_reason' => BotSellOrder::CANCEL_MIGRATION_MERGED,
                        'filled_at'     => null,
                    ]);
                }
                $survivor->update(['amount_to_sell' => $amount, 'share_percent' => $share]);

                return null;
            });
        } catch (\Throwable $e) {
            Log::channel('smart-bot')->error('bot.sell_migration.merge_failed', [
                'survivor_id'  => $survivorId,
                'absorbed_ids' => $absorbedIds,
                'error'        => $e->getMessage(),
            ]);

            return $e->getMessage();
        }
    }

    /**
     * Sell all still-too-small tiers in one market order and settle each with
     * the currency-cancel rule at the real fill price. When even the total is
     * below Binance's minimum, or the market sell fails, tiers settle at the
     * live price and the coin stays on the platform account.
     *
     * @param  list<BotSellOrder>  $sells
     * @param  array<string,string>|null  $filters
     * @return array{0: array<string,mixed>, 1: array<int,array<string,mixed>>}
     */
    private function closeTiers(array $sells, string $market, string $livePrice, ?array $filters, int $migrationId): array
    {
        $results = [];
        $open    = [];
        $amount  = '0';
        foreach ($sells as $sell) {
            $sell->refresh();
            $base = [
                'sell_order_id'         => $sell->id,
                'old_exchange_order_id' => $sell->exchange_order_id,
                'action'                => 'close',
                'amount'                => $this->num($sell->amount_to_sell),
            ];
            if ($sell->status !== BotSellOrder::STATUS_OPEN || $sell->exchange !== BotSellOrder::EXCHANGE_COINEX) {
                $results[$sell->id] = $base + ['outcome' => 'failed', 'error' => 'tier is no longer OPEN on CoinEx'];
                continue;
            }
            $results[$sell->id] = $base;
            $open[]             = $sell;
            $amount             = bcadd($amount, $base['amount'], self::SCALE);
        }

        $close = $this->closePreview($amount, $livePrice, $filters) ?? ['amount' => $amount, 'sellable' => false];
        $close += ['price' => $livePrice, 'price_source' => BotCurrencyCancellation::PRICE_LIVE];

        if ($open !== [] && $close['sellable']) {
            $baseFee = collect($open)
                ->map(fn (BotSellOrder $s) => $s->botBuyExecution)
                ->unique('id')
                ->reduce(fn (string $sum, BotBuyExecution $e) => bcadd($sum, ExchangePositionCloser::baseFeeCoinFromExecution($e), self::SCALE), '0');

            try {
                $res = $this->closer->marketSell($market, $amount, $baseFee);
            } catch (\Throwable $e) {
                $res = null;
                $close['sell_error'] = $e->getMessage();
            }

            if ($res && $res->isFilled() && bccomp($this->num($res->avgPrice), '0', self::SCALE) > 0) {
                $close['price']        = $this->num($res->avgPrice);
                $close['price_source'] = BotCurrencyCancellation::PRICE_MARKET_SELL;
                $close += [
                    'exchange_order_id' => $res->exchangeOrderId,
                    'sold_amount'       => $this->num($res->filledAmount),
                    'exchange_fee'      => $this->num($res->exchangeFee),
                ];
            } elseif ($res) {
                $close['sell_error'] = trim(($res->errorCode ?? '').' '.($res->errorMessage ?? '')) ?: 'market sell not filled';
            }

            if (isset($close['sell_error'])) {
                Log::channel('smart-bot')->error('bot.sell_migration.close_sell_failed', [
                    'migration_id' => $migrationId,
                    'market'       => $market,
                    'amount'       => $amount,
                    'error'        => $close['sell_error'],
                ]);
            }
        }

        $perfPct = $this->num(BotGlobalSettings::current()->performance_fee_percent);
        foreach ($open as $sell) {
            try {
                $b = $this->settlement->settleCurrencyCancel(
                    $sell,
                    $close['price'],
                    $perfPct,
                    cancellationId: null,
                    cancelReason: BotSellOrder::CANCEL_MIGRATION_BELOW_MIN,
                );
            } catch (\Throwable $e) {
                Log::channel('smart-bot')->error('bot.sell_migration.close_settle_failed', [
                    'migration_id'  => $migrationId,
                    'sell_order_id' => $sell->id,
                    'error'         => $e->getMessage(),
                ]);
                $results[$sell->id] += ['outcome' => 'failed', 'error' => $e->getMessage()];
                continue;
            }

            $results[$sell->id] += [
                'outcome' => 'closed',
                'price'   => $close['price'],
                'refund'  => $b['refund'],
                'net_pnl' => $b['net_pnl'],
            ];
        }

        return [$close, $results];
    }

    /**
     * @param  array<string,string>|null  $filters
     * @return array{amount:string, quantity:string, value:string, sellable:bool}|null
     */
    private function closePreview(string $amount, string $livePrice, ?array $filters): ?array
    {
        if (bccomp($amount, '0', self::SCALE) <= 0) {
            return null;
        }

        $quantity = $filters !== null
            ? BinanceOrderFormatter::formatQuantity($amount, BinanceOrderFormatter::stepFor('MARKET', $filters))
            : $amount;

        return [
            'amount'   => $amount,
            'quantity' => $quantity,
            'value'    => bcmul($amount, $livePrice, self::SCALE),
            'sellable' => $filters !== null && ! $this->isBelowMin($quantity, $livePrice, 'MARKET', $filters),
        ];
    }

    private function percentAbove(string $price, string $reference): ?string
    {
        if (bccomp($reference, '0', self::SCALE) <= 0 || bccomp($price, '0', self::SCALE) <= 0) {
            return null;
        }

        return bcmul(bcsub(bcdiv($price, $reference, self::SCALE), '1', self::SCALE), '100', 2);
    }

    private function targetPrice(BotSellOrder $sell): string
    {
        return OpenSellOrdersJob::resolveAbsolutePrice(
            (string) $sell->target_type,
            $this->num($sell->target_value),
            $this->num($sell->botBuyExecution->avg_buy_price),
        );
    }

    private function isHit(string $livePrice, string $targetPrice): bool
    {
        return bccomp($livePrice, '0', self::SCALE) > 0 && bccomp($livePrice, $targetPrice, self::SCALE) >= 0;
    }

    /**
     * @param array<string,string> $filters
     */
    private function isBelowMin(string $quantity, string $price, string $orderType, array $filters): bool
    {
        $minQty = BinanceOrderFormatter::minQtyFor($orderType, $filters) ?? '0';
        if (bccomp($quantity, '0', self::SCALE) <= 0 || bccomp($quantity, $minQty, self::SCALE) < 0) {
            return true;
        }

        $minNotional = BinanceOrderFormatter::minNotionalFor($orderType, $filters);

        return $minNotional !== null && bccomp(bcmul($quantity, $price, self::SCALE), $minNotional, self::SCALE) < 0;
    }

    /**
     * @return array<string,string>|null
     */
    private function filters(string $market): ?array
    {
        try {
            return BinanceRequest::symbolFilters(BinanceOrderFormatter::normalizeSymbol($market));
        } catch (\Throwable $e) {
            Log::channel('smart-bot')->warning('bot.sell_migration.filters_failed', [
                'market' => $market,
                'error'  => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * @return array{available:string, frozen:string}|null null when Binance cannot be reached
     */
    private function binanceBalance(string $symbol): ?array
    {
        try {
            $balances = AssetFactory::make('binance')->getBalance();
        } catch (\Throwable $e) {
            Log::channel('smart-bot')->warning('bot.sell_migration.balance_failed', ['error' => $e->getMessage()]);

            return null;
        }

        foreach ($balances as $dto) {
            if (strtoupper((string) $dto->getCcy()) === $symbol) {
                return [
                    'available' => $this->num($dto->getAvailable()),
                    'frozen'    => $this->num($dto->getFrozen()),
                ];
            }
        }

        return ['available' => '0', 'frozen' => '0'];
    }

    /**
     * @return Collection<int,BotSellOrder>
     */
    private function pendingSells(int $currencyId): Collection
    {
        return BotSellOrder::query()
            ->where('status', BotSellOrder::STATUS_OPEN)
            ->where('exchange', BotSellOrder::EXCHANGE_COINEX)
            ->whereHas('botBuyExecution', fn ($q) => $q->where('currency_id', $currencyId))
            ->with(['botBuyExecution.currency', 'botBuyExecution.botOrder.user'])
            ->orderBy('id')
            ->get();
    }

    private function filledTiersCount(int $currencyId): int
    {
        return BotSellOrder::query()
            ->where('status', BotSellOrder::STATUS_FILLED)
            ->whereHas('botBuyExecution', fn ($q) => $q->where('currency_id', $currencyId))
            ->count();
    }

    private function runningMigration(int $currencyId): ?BotSellMigration
    {
        return BotSellMigration::where('currency_id', $currencyId)
            ->whereIn('status', [BotSellMigration::STATUS_PENDING, BotSellMigration::STATUS_RUNNING])
            ->first();
    }

    private function runningCancellation(int $currencyId): ?BotCurrencyCancellation
    {
        return BotCurrencyCancellation::where('currency_id', $currencyId)
            ->whereIn('status', [BotCurrencyCancellation::STATUS_PENDING, BotCurrencyCancellation::STATUS_RUNNING])
            ->first();
    }

    private function livePrice(Currency $currency): string
    {
        try {
            return $this->num($this->priceFeed->getLive($currency->id));
        } catch (\Throwable $e) {
            Log::channel('smart-bot')->warning('bot.sell_migration.no_live_price', [
                'currency' => $currency->symbol,
                'error'    => $e->getMessage(),
            ]);

            return '0';
        }
    }

    private function num(mixed $v): string
    {
        if ($v === null || $v === '') {
            return '0';
        }
        if (is_int($v) || is_float($v)) {
            return number_format((float) $v, self::SCALE, '.', '');
        }
        $s = trim((string) $v);
        if ($s === '' || ! is_numeric($s)) {
            return '0';
        }
        if (stripos($s, 'e') !== false) {
            return number_format((float) $s, self::SCALE, '.', '');
        }

        return $s;
    }
}

<?php

namespace App\Services\Bot;

use App\Events\Bot\BotSellOrderFilled;
use App\Jobs\Bot\MigrateSellOrdersJob;
use App\Jobs\Bot\OpenSellOrdersJob;
use App\Models\Bot\BotCurrencyCancellation;
use App\Models\Bot\BotSellMigration;
use App\Models\Bot\BotSellOrder;
use App\Models\Currency;
use App\Services\Bot\ReferenceExchange\ExchangeContract;
use App\Services\Bot\ReferenceExchange\ExchangeOrderResult;
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
 * FILLED / CANCELED tiers are never touched. A run refuses to start when the
 * coin's free Binance balance cannot cover every remaining tier.
 */
class BotSellMigrationService
{
    private const SCALE = 8;

    public function __construct(
        private readonly ExchangeContract $exchange,
        private readonly PriceFeed $priceFeed,
        private readonly BotOrderDescriptionService $descriptions,
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

        $tiers  = [];
        $totals = [
            'tiers'         => 0,
            'limit_tiers'   => 0,
            'market_tiers'  => 0,
            'below_min'     => 0,
            'limit_amount'  => '0',
            'market_amount' => '0',
        ];
        $required = '0';
        $users    = [];
        $orders   = [];

        foreach ($sells as $sell) {
            $execution   = $sell->botBuyExecution;
            $amount      = $this->num($sell->amount_to_sell);
            $targetPrice = $this->targetPrice($sell);
            $action      = $this->isHit($livePrice, $targetPrice) ? 'market' : 'limit';
            $orderType   = $action === 'market' ? 'MARKET' : 'LIMIT';
            $quantity    = $filters !== null
                ? BinanceOrderFormatter::formatQuantity($amount, BinanceOrderFormatter::stepFor($orderType, $filters))
                : $amount;
            $belowMin = $filters !== null && $this->isBelowMin($quantity, $action === 'market' ? $livePrice : $targetPrice, $orderType, $filters);

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
                'target_price'      => $targetPrice,
                'amount'            => $amount,
                'binance_quantity'  => $quantity,
                'action'            => $action,
                'below_min'         => $belowMin,
            ];

            $totals['tiers']++;
            $totals[$action.'_tiers']++;
            $totals[$action.'_amount'] = bcadd($totals[$action.'_amount'], $amount, self::SCALE);
            $totals['below_min'] += $belowMin ? 1 : 0;
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

        $results    = [];
        $counts     = ['placed' => 0, 'completed' => 0, 'failed' => 0];
        $executions = [];

        foreach ($sells as $sell) {
            $result = $this->migrateTier($sell, $market, $livePrice, $migration->id);
            $results[] = $result;
            $counts[$result['outcome']]++;

            if ($result['outcome'] !== 'failed') {
                $executions[$sell->bot_buy_execution_id] = $sell->botBuyExecution;
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
            'failed_count'    => $counts['failed'],
            'required_amount' => $required,
            'details'         => ['live_price' => $livePrice, 'tiers' => $results],
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

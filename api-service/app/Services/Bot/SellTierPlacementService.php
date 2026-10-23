<?php

namespace App\Services\Bot;

use App\Events\Bot\BotSellOrderFilled;
use App\Jobs\Bot\OpenSellOrdersJob;
use App\Models\Bot\BotCurrencyCancellation;
use App\Models\Bot\BotGlobalSettings;
use App\Models\Bot\BotSellMigration;
use App\Models\Bot\BotSellOrder;
use App\Services\Bot\ReferenceExchange\ExchangeContract;
use App\Services\Bot\ReferenceExchange\ExchangeOrderResult;
use App\Services\Bot\ReferenceExchange\ExchangeOrderStatus;
use App\Services\Exchanges\Asset\Binance\BinanceOrderFormatter;
use App\Services\Exchanges\Asset\Binance\BinanceRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Sell tiers Binance refuses for now stay OPEN with placement_status = WAITING
 * and no exchange order, for one of two reasons (waiting_reason):
 *   - price_ceiling: price above PERCENT_PRICE_BY_SIDE (avg price × askMultiplierUp);
 *   - order_limit:   the symbol's MAX_NUM_ORDERS open-order limit is used up.
 * They are still unsold positions everywhere else (cancel, settle, exposure).
 * tryPlace() puts one on the exchange once it fits, or market-sells it when
 * the price already passed the target; rotateOut() frees a slot by sending a
 * far, untouched order back to WAITING.
 */
class SellTierPlacementService
{
    private const SCALE = 8;

    /** Binance's usual per-symbol limit when exchangeInfo does not report one. */
    private const DEFAULT_MAX_NUM_ORDERS = 200;

    public function __construct(private readonly ExchangeContract $exchange) {}

    public function exchangeName(): string
    {
        return $this->exchange->name();
    }

    /**
     * Binance's sell-side ceiling for $market. `place_max_price` is the ceiling
     * minus the configured safety margin and is what placement decisions use.
     * Falls back to $livePrice when Binance's average price is unavailable.
     *
     * @return array{multiplier_up:string, multiplier_down:?string, avg_price_mins:?int, reference_price:string, reference:string, max_price:string, place_max_price:string, max_percent_above:string}|null
     */
    public function ceiling(string $market, string $livePrice = '0'): ?array
    {
        $symbol = BinanceOrderFormatter::normalizeSymbol($market);

        try {
            $filters = BinanceRequest::symbolFilters($symbol);
        } catch (\Throwable $e) {
            Log::channel('smart-bot')->warning('bot.waiting.filters_failed', ['market' => $symbol, 'error' => $e->getMessage()]);

            return null;
        }

        $up = $filters['askMultiplierUp'] ?? null;
        if (! is_numeric($up) || bccomp($up, '0', self::SCALE) <= 0) {
            return null;
        }

        $reference = 'avg';
        try {
            $refPrice = $this->num(Cache::remember(
                'bot:waiting:avg-price:'.$symbol,
                now()->addSeconds(20),
                fn () => BinanceRequest::averagePrice($symbol),
            ));
        } catch (\Throwable $e) {
            Log::channel('smart-bot')->warning('bot.waiting.avg_price_failed', ['market' => $symbol, 'error' => $e->getMessage()]);
            $refPrice = '0';
        }
        if (bccomp($refPrice, '0', self::SCALE) <= 0) {
            $reference = 'live';
            $refPrice  = $this->num($livePrice);
        }
        if (bccomp($refPrice, '0', self::SCALE) <= 0) {
            return null;
        }

        $maxPrice = bcmul($refPrice, $up, self::SCALE);
        $margin   = bcdiv(number_format((float) BotGlobalSettings::current()->waiting_safety_margin_percent, 4, '.', ''), '100', self::SCALE);
        $mins     = $filters['avgPriceMins'] ?? null;

        return [
            'multiplier_up'     => $up,
            'multiplier_down'   => $filters['askMultiplierDown'] ?? null,
            'avg_price_mins'    => is_numeric($mins) ? (int) $mins : null,
            'reference_price'   => $refPrice,
            'reference'         => $reference,
            'max_price'         => $maxPrice,
            'place_max_price'   => bcmul($maxPrice, bcsub('1', $margin, self::SCALE), self::SCALE),
            'max_percent_above' => bcmul(bcsub($up, '1', self::SCALE), '100', 2),
        ];
    }

    /**
     * Ceiling for the bot's own exchange; null for the fake / test-lab drivers.
     */
    public function ceilingFor(string $market, string $livePrice = '0'): ?array
    {
        if ($this->exchange->name() !== BotSellOrder::EXCHANGE_BINANCE) {
            return null;
        }

        return $this->ceiling($market, $livePrice);
    }

    public function exceedsCeiling(string $price, ?array $ceiling): bool
    {
        return $ceiling !== null && bccomp($price, $ceiling['place_max_price'], self::SCALE) > 0;
    }

    public function isPriceRangeRejection(ExchangeOrderResult $res): bool
    {
        return str_contains(($res->errorCode ?? '').' '.($res->errorMessage ?? ''), 'PERCENT_PRICE');
    }

    public function isOrderLimitRejection(ExchangeOrderResult $res): bool
    {
        return str_contains(($res->errorCode ?? '').' '.($res->errorMessage ?? ''), 'MAX_NUM_ORDERS');
    }

    /**
     * waiting_reason for a rejection that should keep the tier WAITING; null
     * for any other failure.
     */
    public function waitReasonFor(ExchangeOrderResult $res): ?string
    {
        return match (true) {
            $this->isPriceRangeRejection($res) => BotSellOrder::WAITING_PRICE_CEILING,
            $this->isOrderLimitRejection($res) => BotSellOrder::WAITING_ORDER_LIMIT,
            default                            => null,
        };
    }

    /**
     * The shared account's open orders on $market as orderId => executedQty;
     * null for non-Binance drivers or when Binance cannot be reached.
     *
     * @return array<string,string>|null
     */
    public function openOrders(string $market, bool $fresh = false): ?array
    {
        if ($this->exchange->name() !== BotSellOrder::EXCHANGE_BINANCE) {
            return null;
        }

        $symbol = BinanceOrderFormatter::normalizeSymbol($market);
        $key    = 'bot:waiting:open-orders:'.$symbol;
        if ($fresh) {
            Cache::forget($key);
        }

        try {
            return Cache::remember($key, now()->addSeconds(10), function () use ($symbol) {
                $orders = [];
                foreach (BinanceRequest::openOrders($symbol) as $order) {
                    $orders[(string) $order['orderId']] = $this->num($order['executedQty'] ?? '0');
                }

                return $orders;
            });
        } catch (\Throwable $e) {
            Log::channel('smart-bot')->warning('bot.waiting.open_orders_failed', ['market' => $symbol, 'error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * MAX_NUM_ORDERS usage for $market: `free` is what the bot may still place
     * after leaving `reserve` slots to the rest of the account.
     *
     * @param  array<string,string>|null  $openOrders  {@see openOrders()}
     * @return array{max:int, reserve:int, open:int, free:int}|null
     */
    public function orderSlots(string $market, ?array $openOrders): ?array
    {
        if ($openOrders === null) {
            return null;
        }

        try {
            $filters = BinanceRequest::symbolFilters(BinanceOrderFormatter::normalizeSymbol($market));
        } catch (\Throwable $e) {
            $filters = [];
        }

        $max     = is_numeric($filters['maxNumOrders'] ?? null) ? (int) $filters['maxNumOrders'] : self::DEFAULT_MAX_NUM_ORDERS;
        $reserve = max(0, (int) BotGlobalSettings::current()->waiting_order_slot_reserve);
        $open    = count($openOrders);

        return [
            'max'     => $max,
            'reserve' => $reserve,
            'open'    => $open,
            'free'    => max(0, $max - $reserve - $open),
        ];
    }

    public static function errorText(ExchangeOrderResult $res): string
    {
        return trim(($res->errorCode ?? '').' '.($res->errorMessage ?? '')) ?: 'order not placed';
    }

    /**
     * Columns that put a tier (new or existing) into the WAITING state.
     *
     * @return array<string,mixed>
     */
    public function waitingAttributes(?string $error, string $reason = BotSellOrder::WAITING_PRICE_CEILING): array
    {
        return [
            'exchange_order_id'       => null,
            'exchange'                => $this->exchange->name(),
            'placement_status'        => BotSellOrder::PLACEMENT_WAITING,
            'waiting_reason'          => $reason,
            'waiting_since'           => now(),
            'last_placement_error'    => $error !== null ? mb_substr($error, 0, 250) : null,
            'last_placement_check_at' => now(),
        ];
    }

    public function targetPrice(BotSellOrder $tier): string
    {
        return OpenSellOrdersJob::resolveAbsolutePrice(
            (string) $tier->target_type,
            $this->num($tier->target_value),
            $this->num($tier->botBuyExecution->avg_buy_price),
        );
    }

    /**
     * Try to put one WAITING tier on the exchange:
     *   - price already at/above target → MARKET SELL + the regular fill path;
     *   - target within the ceiling     → LIMIT SELL at the target;
     *   - otherwise / rejected          → stays WAITING (attempt + error recorded).
     * Never cancels or force-sells anything else.
     *
     * @return array{outcome:string, sell_order_id:int, target_price?:string, error?:string, exchange_order_id?:string, filled_amount?:string, avg_price?:string}
     */
    public function tryPlace(BotSellOrder $tier, string $livePrice, ?array $ceiling): array
    {
        $market = strtoupper((string) $tier->botBuyExecution->currency->symbol).'USDT';
        $target = $this->targetPrice($tier);
        $isHit  = bccomp($livePrice, '0', self::SCALE) > 0 && bccomp($livePrice, $target, self::SCALE) >= 0;
        $base   = ['sell_order_id' => $tier->id, 'target_price' => $target];

        if (! $isHit && $this->exceedsCeiling($target, $ceiling)) {
            BotSellOrder::whereKey($tier->id)->update([
                'waiting_reason'          => BotSellOrder::WAITING_PRICE_CEILING,
                'last_placement_check_at' => now(),
            ]);

            return $base + ['outcome' => 'waiting', 'waiting_reason' => BotSellOrder::WAITING_PRICE_CEILING];
        }

        try {
            /** @var array{0:?BotSellOrder,1:?ExchangeOrderResult,2:bool} $outcome */
            $outcome = DB::transaction(function () use ($tier, $market, $target, $isHit) {
                $fresh = BotSellOrder::whereKey($tier->id)->lockForUpdate()->first();
                if (! $fresh || $fresh->status !== BotSellOrder::STATUS_OPEN || $fresh->placement_status !== BotSellOrder::PLACEMENT_WAITING) {
                    return [null, null, false];
                }
                // A currency cancel reads tiers without locking them; never put an
                // order on the exchange once one is queued for this coin.
                if ($this->blockReason((int) $tier->botBuyExecution->currency_id) !== null) {
                    return [null, null, false];
                }

                $amount = $this->num($fresh->amount_to_sell);
                $res    = $isHit
                    ? $this->exchange->placeMarketSell($market, $amount)
                    : $this->exchange->placeLimitSell($market, $amount, $target);
                $ok = $res->exchangeOrderId !== null
                    && (! $isHit || bccomp($this->num($res->filledAmount), '0', self::SCALE) > 0);

                $fresh->update($ok ? array_filter([
                    'exchange_order_id'       => $res->exchangeOrderId,
                    'exchange'                => $this->exchange->name(),
                    'placement_status'        => BotSellOrder::PLACEMENT_PLACED,
                    'waiting_reason'          => null,
                    'placement_attempts'      => (int) $fresh->placement_attempts + 1,
                    'last_placement_error'    => null,
                    'last_placement_check_at' => now(),
                    'sell_ref_exchange_fee'   => $isHit ? ($res->exchangeFee ?? '0') : null,
                ], fn ($v, $k) => $v !== null || in_array($k, ['last_placement_error', 'waiting_reason'], true), ARRAY_FILTER_USE_BOTH) : array_filter([
                    'placement_attempts'      => (int) $fresh->placement_attempts + 1,
                    'waiting_reason'          => $this->waitReasonFor($res),
                    'last_placement_error'    => mb_substr(self::errorText($res), 0, 250),
                    'last_placement_check_at' => now(),
                ], fn ($v) => $v !== null));

                return [$fresh, $res, $ok];
            });
        } catch (\Throwable $e) {
            Log::channel('smart-bot')->error('bot.waiting.place_exception', [
                'sell_order_id' => $tier->id,
                'error'         => $e->getMessage(),
            ]);
            BotSellOrder::whereKey($tier->id)->update([
                'last_placement_error'    => mb_substr($e->getMessage(), 0, 250),
                'last_placement_check_at' => now(),
            ]);

            return $base + ['outcome' => 'waiting', 'error' => $e->getMessage()];
        }

        [$fresh, $res, $ok] = $outcome;

        if ($fresh === null) {
            return $base + ['outcome' => 'skipped', 'error' => 'tier is no longer waiting or its coin is being canceled/migrated'];
        }

        if (! $ok) {
            $error  = self::errorText($res);
            $reason = $this->waitReasonFor($res);
            $level  = $reason === null && (str_contains($error, '-2010') || stripos($error, 'insufficient') !== false) ? 'critical' : 'warning';
            Log::channel('smart-bot')->{$level}('bot.waiting.place_rejected', [
                'sell_order_id' => $tier->id,
                'market'        => $market,
                'action'        => $isHit ? 'market' : 'limit',
                'error'         => $error,
            ]);

            return $base + ['outcome' => 'waiting', 'error' => $error, 'waiting_reason' => $reason];
        }

        if (! $isHit) {
            return $base + ['outcome' => 'placed', 'exchange_order_id' => (string) $res->exchangeOrderId];
        }

        BotSellOrderFilled::dispatch(
            $fresh,
            $this->num($res->filledAmount),
            $this->num($res->avgPrice),
            '0',
            $res->exchangeFee ?? '0',
        );

        return $base + [
            'outcome'           => 'completed',
            'exchange_order_id' => (string) $res->exchangeOrderId,
            'filled_amount'     => $this->num($res->filledAmount),
            'avg_price'         => $this->num($res->avgPrice),
        ];
    }

    /**
     * Free one open-order slot: cancel a PLACED tier's limit sell and, once
     * Binance confirms it canceled with nothing executed, return the tier to
     * WAITING (order_limit). A tier that filled meanwhile is left to
     * bot:sync-sell-orders; any other answer leaves the row untouched.
     *
     * @return array{outcome:string, sell_order_id:int, exchange_order_id?:string, error?:string}
     *         outcome: rotated | filled | skipped | failed
     */
    public function rotateOut(BotSellOrder $tier, string $market): array
    {
        $base = ['sell_order_id' => $tier->id, 'exchange_order_id' => (string) $tier->exchange_order_id];

        try {
            return DB::transaction(function () use ($tier, $market, $base) {
                $fresh = BotSellOrder::whereKey($tier->id)->lockForUpdate()->first();
                if (
                    ! $fresh
                    || $fresh->status !== BotSellOrder::STATUS_OPEN
                    || $fresh->placement_status !== BotSellOrder::PLACEMENT_PLACED
                    || $fresh->exchange !== $this->exchange->name()
                    || (string) $fresh->exchange_order_id !== $base['exchange_order_id']
                ) {
                    return $base + ['outcome' => 'skipped', 'error' => 'tier is no longer an open placed order'];
                }
                if ($this->blockReason((int) $tier->botBuyExecution->currency_id) !== null) {
                    return $base + ['outcome' => 'skipped', 'error' => 'coin is being canceled/migrated'];
                }

                $this->exchange->cancelOrder($market, $base['exchange_order_id']);
                $check    = $this->exchange->getOrder($market, $base['exchange_order_id']);
                $executed = $this->num($check->filledAmount);

                if ($check->status === ExchangeOrderStatus::CANCELED && bccomp($executed, '0', self::SCALE) <= 0) {
                    $fresh->update($this->waitingAttributes(
                        "rotated out: order {$base['exchange_order_id']} canceled to free a MAX_NUM_ORDERS slot for a nearer tier",
                        BotSellOrder::WAITING_ORDER_LIMIT,
                    ));

                    return $base + ['outcome' => 'rotated'];
                }
                if ($check->status === ExchangeOrderStatus::FILLED) {
                    return $base + ['outcome' => 'filled'];
                }

                return $base + ['outcome' => 'failed', 'error' => sprintf(
                    'after cancel the order is %s with executed %s',
                    $check->status->name,
                    $executed,
                )];
            });
        } catch (\Throwable $e) {
            return $base + ['outcome' => 'failed', 'error' => $e->getMessage()];
        }
    }

    /**
     * Admin-facing reason this coin's tiers must not be placed right now.
     */
    public function blockReason(int $currencyId): ?string
    {
        $migration = BotSellMigration::where('currency_id', $currencyId)
            ->whereIn('status', [BotSellMigration::STATUS_PENDING, BotSellMigration::STATUS_RUNNING])
            ->first();
        if ($migration) {
            return "انتقال پله‌های این ارز (#{$migration->id}) در حال اجراست.";
        }

        $cancellation = BotCurrencyCancellation::where('currency_id', $currencyId)
            ->whereIn('status', [BotCurrencyCancellation::STATUS_PENDING, BotCurrencyCancellation::STATUS_RUNNING])
            ->first();

        return $cancellation ? "لغو ارز (#{$cancellation->id}) برای این ارز در حال اجراست." : null;
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

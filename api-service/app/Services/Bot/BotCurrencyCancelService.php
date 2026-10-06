<?php

namespace App\Services\Bot;

use App\Jobs\Bot\CancelCurrencyPositionsJob;
use App\Models\Bot\BotBuyExecution;
use App\Models\Bot\BotCurrencyCancellation;
use App\Models\Bot\BotGlobalSettings;
use App\Models\Bot\BotOrder;
use App\Models\Bot\BotSellOrder;
use App\Models\Bot\BotSignal;
use App\Models\Currency;
use App\Services\Bot\ReferenceExchange\ExchangeContract;
use App\Services\Bot\ReferenceExchange\ExchangeOrderStatus;
use App\Services\Bot\ReferenceExchange\ExchangePositionCloser;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Admin "cancel one coin across every bot order": every OPEN sell tier of the
 * coin is settled back into its owner's bot wallet (see
 * SettlementService::currencyCancelBreakdown for the refund rule).
 *
 * Reference-exchange handling is chosen per run by the admin:
 *   - cancel_on_exchange: cancel each tier's limit sell on the exchange first;
 *   - sell_on_exchange (requires the above): then market-sell the combined
 *     amount in ONE order, and settle every tier at that fill price.
 * With neither, the exchange is not contacted and tiers settle at the live price.
 *
 * The run is queued (CancelCurrencyPositionsJob); start() only validates,
 * disables the coin's signal and records the request.
 */
class BotCurrencyCancelService
{
    private const SCALE = 8;

    public function __construct(
        private readonly SettlementService $settlement,
        private readonly ExchangeContract $exchange,
        private readonly ExchangePositionCloser $closer,
        private readonly PriceFeed $priceFeed,
        private readonly BotOrderDescriptionService $descriptions,
    ) {}

    /**
     * @return array<string,mixed>
     */
    public function preview(Currency $currency): array
    {
        $perfPct = $this->num(BotGlobalSettings::current()->performance_fee_percent);
        $price   = $this->livePrice($currency);
        $sells   = $this->openSells($currency->id);

        $users  = [];
        $totals = $this->emptyTotals();

        foreach ($sells as $sell) {
            $execution = $sell->botBuyExecution;
            $amount    = $this->num($sell->amount_to_sell);
            $b         = $this->settlement->currencyCancelBreakdown($execution, $amount, $price, $perfPct);
            $user      = $execution->botOrder->user;
            $userId    = (int) $execution->botOrder->user_id;

            $users[$userId] ??= [
                'user_id'  => $userId,
                'email'    => $user?->email,
                'mobile'   => $user?->mobile,
                'orders'   => [],
                'tiers'    => 0,
            ] + $this->emptyTotals();

            $users[$userId]['orders'][$execution->bot_order_id] = true;
            $users[$userId]['tiers']++;
            $this->accumulate($users[$userId], $amount, $b);
            $this->accumulate($totals, $amount, $b);
        }

        $users = array_values(array_map(function (array $u) {
            $u['orders_count'] = count($u['orders']);
            unset($u['orders']);

            return $u;
        }, $users));

        return [
            'currency' => [
                'id'     => $currency->id,
                'symbol' => strtoupper((string) $currency->symbol),
                'name'   => $currency->name,
            ],
            'live_price'              => $price,
            'performance_fee_percent' => $perfPct,
            'signal_active'           => (bool) BotSignal::where('currency_id', $currency->id)->value('is_active'),
            'in_flight'               => $this->inFlight($currency->id),
            'running'                 => $this->runningCancellation($currency->id)?->id,
            'totals'                  => $totals + [
                'users'        => count($users),
                'orders'       => $sells->pluck('botBuyExecution.bot_order_id')->unique()->count(),
                'sell_orders'  => $sells->count(),
            ],
            'users' => $users,
        ];
    }

    /**
     * @throws \DomainException with an admin-facing message when the run cannot start.
     */
    public function start(
        Currency $currency,
        bool $cancelOnExchange,
        bool $sellOnExchange,
        string $reason,
        ?int $adminId,
        ?string $adminLabel,
    ): BotCurrencyCancellation {
        if ($sellOnExchange && ! $cancelOnExchange) {
            throw new \DomainException('فروش ارز در صرافی مرجع فقط بعد از لغو پله‌های فروش در صرافی مرجع ممکن است.');
        }

        $cancellation = DB::transaction(function () use ($currency, $cancelOnExchange, $sellOnExchange, $reason, $adminId, $adminLabel) {
            Currency::whereKey($currency->id)->lockForUpdate()->first();

            if ($running = $this->runningCancellation($currency->id)) {
                throw new \DomainException("لغو دیگری برای این ارز (#{$running->id}) در حال اجراست.");
            }

            $inFlight = $this->inFlight($currency->id);
            if ($inFlight['buys_in_flight'] > 0 || $inFlight['sells_pending'] > 0) {
                throw new \DomainException('برخی سفارش‌های این ارز هنوز در حال خرید یا ثبت پله‌های فروش در صرافی مرجع هستند. چند لحظه بعد دوباره تلاش کنید.');
            }

            if (! $this->openSells($currency->id)->count()) {
                throw new \DomainException('برای این ارز پله فروش بازی در سفارش‌های کاربران وجود ندارد.');
            }

            // Stop new buys of this coin before we start unwinding it.
            BotSignal::where('currency_id', $currency->id)->update(['is_active' => false]);

            return BotCurrencyCancellation::create([
                'currency_id'        => $currency->id,
                'admin_id'           => $adminId,
                'admin_label'        => $adminLabel,
                'cancel_on_exchange' => $cancelOnExchange,
                'sell_on_exchange'   => $sellOnExchange,
                'reason'             => $reason,
                'status'             => BotCurrencyCancellation::STATUS_PENDING,
            ]);
        });

        Log::channel('smart-bot')->info('bot.currency_cancel.queued', [
            'cancellation_id'    => $cancellation->id,
            'currency'           => $currency->symbol,
            'cancel_on_exchange' => $cancelOnExchange,
            'sell_on_exchange'   => $sellOnExchange,
            'admin_id'           => $adminId,
        ]);

        CancelCurrencyPositionsJob::dispatch($cancellation->id)->onQueue('bot-settlement');

        return $cancellation->fresh();
    }

    public function run(int $cancellationId): void
    {
        $claimed = BotCurrencyCancellation::whereKey($cancellationId)
            ->where('status', BotCurrencyCancellation::STATUS_PENDING)
            ->update(['status' => BotCurrencyCancellation::STATUS_RUNNING, 'started_at' => now()]);
        if (! $claimed) {
            return;
        }

        $cancellation = BotCurrencyCancellation::with('currency')->findOrFail($cancellationId);

        try {
            $this->execute($cancellation);
        } catch (\Throwable $e) {
            Log::channel('smart-bot')->error('bot.currency_cancel.exception', [
                'cancellation_id' => $cancellationId,
                'exception'       => $e::class,
                'message'         => $e->getMessage(),
                'file'            => $e->getFile().':'.$e->getLine(),
            ]);

            $cancellation->update([
                'status'      => BotCurrencyCancellation::STATUS_FAILED,
                'error'       => $e->getMessage(),
                'finished_at' => now(),
            ]);
        }
    }

    private function execute(BotCurrencyCancellation $cancellation): void
    {
        $currency = $cancellation->currency;
        $symbol   = strtoupper((string) $currency->symbol);
        $market   = $symbol.'USDT';
        $perfPct  = $this->num(BotGlobalSettings::current()->performance_fee_percent);
        $details  = ['skipped' => [], 'partial_fills' => [], 'errors' => []];

        // Without a market sell the live price is the settlement price; refuse
        // to run (before touching the exchange) rather than settle at zero.
        $livePrice = $this->livePrice($currency);
        if (! $cancellation->sell_on_exchange && bccomp($livePrice, '0', self::SCALE) <= 0) {
            throw new \RuntimeException("قیمت لحظه‌ای {$symbol} در دسترس نیست؛ لغو انجام نشد.");
        }

        $sells = $this->openSells($currency->id);

        // 1) Cancel the limit sells on the reference exchange.
        $toSettle     = [];
        $sellableCoin = '0';
        foreach ($sells as $sell) {
            $amount = $this->num($sell->amount_to_sell);

            if ($cancellation->cancel_on_exchange && $sell->exchange_order_id) {
                ['skip' => $skip, 'filled' => $filled] = $this->cancelOnExchange($sell, $market);
                if ($skip !== null) {
                    $details['skipped'][] = ['sell_order_id' => $sell->id, 'reason' => $skip];
                    continue;
                }
                // Partially filled before the cancel: that part of the coin is
                // already sold, so only the remainder is market-sold below.
                if (bccomp($filled, '0', self::SCALE) > 0) {
                    $amount = bcsub($amount, $filled, self::SCALE);
                    $details['partial_fills'][] = ['sell_order_id' => $sell->id, 'filled' => $filled];
                }
            }

            $toSettle[]   = $sell;
            $sellableCoin = bcadd($sellableCoin, bccomp($amount, '0', self::SCALE) > 0 ? $amount : '0', self::SCALE);
        }

        // 2) One aggregated market sell for everything freed on the exchange.
        $price       = $livePrice;
        $priceSource = BotCurrencyCancellation::PRICE_LIVE;
        $sellData    = [];
        if ($cancellation->sell_on_exchange && $toSettle !== [] && bccomp($sellableCoin, '0', self::SCALE) > 0) {
            $baseFee = collect($toSettle)
                ->map(fn (BotSellOrder $s) => $s->botBuyExecution)
                ->unique('id')
                ->reduce(fn (string $sum, BotBuyExecution $e) => bcadd($sum, ExchangePositionCloser::baseFeeCoinFromExecution($e), self::SCALE), '0');

            try {
                $result = $this->closer->marketSell($market, $sellableCoin, $baseFee);
            } catch (\Throwable $e) {
                $result = null;
                $sellData['sell_error'] = $e->getMessage();
            }

            if ($result && $result->isFilled() && bccomp($this->num($result->avgPrice), '0', self::SCALE) > 0) {
                $price       = $this->num($result->avgPrice);
                $priceSource = BotCurrencyCancellation::PRICE_MARKET_SELL;
                $sellData   += [
                    'sell_exchange_order_id'     => $result->exchangeOrderId,
                    'sold_amount'                => $this->num($result->filledAmount),
                    'sell_exchange_fee'          => $this->num($result->exchangeFee),
                    'sell_exchange_fee_currency' => $result->feeCurrency,
                ];
            } elseif ($result) {
                $sellData['sell_error'] = trim(($result->errorCode ?? '').' '.($result->errorMessage ?? '')) ?: 'market sell not filled';
            }

            if (isset($sellData['sell_error'])) {
                Log::channel('smart-bot')->error('bot.currency_cancel.market_sell_failed', [
                    'cancellation_id' => $cancellation->id,
                    'market'          => $market,
                    'amount'          => $sellableCoin,
                    'error'           => $sellData['sell_error'],
                ]);
            }
        }

        // 3) Settle every tier back into its owner's bot wallet.
        $totals     = $this->emptyTotals();
        $userIds    = [];
        $orderIds   = [];
        $executions = [];
        $settled    = 0;
        foreach ($toSettle as $sell) {
            try {
                $b = $this->settlement->settleCurrencyCancel($sell, $price, $perfPct, $cancellation->id);
            } catch (\Throwable $e) {
                $details['errors'][] = ['sell_order_id' => $sell->id, 'error' => $e->getMessage()];
                Log::channel('smart-bot')->error('bot.currency_cancel.settle_failed', [
                    'cancellation_id' => $cancellation->id,
                    'sell_order_id'   => $sell->id,
                    'error'           => $e->getMessage(),
                ]);
                continue;
            }

            $execution = $sell->botBuyExecution;
            $this->accumulate($totals, $this->num($sell->amount_to_sell), $b);
            $userIds[$execution->botOrder->user_id] = true;
            $orderIds[$execution->bot_order_id]     = true;
            $executions[$execution->id]             = $execution;
            $settled++;
        }

        // 4) Close unwound executions, finalize emptied orders, leave notes.
        foreach ($executions as $execution) {
            $this->closeExecutionIfUnwound($execution->id);
            $this->descriptions->appendSystemNote($execution, sprintf(
                'لغو ارز توسط ادمین (#%d): %s',
                $cancellation->id,
                $cancellation->reason,
            ));
        }
        foreach (array_keys($orderIds) as $orderId) {
            $this->cancelOrderIfEmpty($orderId);
        }

        $cancellation->update($sellData + [
            'status'                => BotCurrencyCancellation::STATUS_DONE,
            'price_source'          => $priceSource,
            'settlement_price'      => $price,
            'users_count'           => count($userIds),
            'orders_count'          => count($orderIds),
            'sell_orders_count'     => $settled,
            'skipped_count'         => count($details['skipped']) + count($details['errors']),
            'total_amount'          => $totals['amount'],
            'total_principal'       => $totals['principal'],
            'total_performance_fee' => $totals['performance_fee'],
            'total_refund'          => $totals['refund'],
            'details'               => $details,
            'finished_at'           => now(),
        ]);

        Log::channel('smart-bot')->info('bot.currency_cancel.done', [
            'cancellation_id' => $cancellation->id,
            'currency'        => $symbol,
            'settled'         => $settled,
            'price'           => $price,
            'price_source'    => $priceSource,
            'total_refund'    => $totals['refund'],
            'skipped'         => count($details['skipped']),
            'errors'          => count($details['errors']),
        ]);
    }

    /**
     * Cancel one tier's limit sell. `skip` is set when the tier must be left
     * OPEN (already filled on the exchange, or the cancel failed and the order
     * may still be live) so the regular sync settles it instead. `filled` is
     * the coin a partial fill already sold before the cancel.
     *
     * @return array{skip:?string, filled:string}
     */
    private function cancelOnExchange(BotSellOrder $sell, string $market): array
    {
        try {
            $this->exchange->cancelOrder($market, (string) $sell->exchange_order_id);
        } catch (\Throwable $e) {
            Log::channel('smart-bot')->warning('bot.currency_cancel.exchange_cancel_failed', [
                'sell_order_id'     => $sell->id,
                'exchange_order_id' => $sell->exchange_order_id,
                'error'             => $e->getMessage(),
            ]);

            return ['skip' => 'exchange_cancel_failed: '.$e->getMessage(), 'filled' => '0'];
        }

        try {
            $status = $this->exchange->getOrder($market, (string) $sell->exchange_order_id);
        } catch (\Throwable) {
            return ['skip' => null, 'filled' => '0'];
        }

        if ($status->status === ExchangeOrderStatus::FILLED) {
            return ['skip' => 'already_filled_on_exchange', 'filled' => '0'];
        }

        return ['skip' => null, 'filled' => $this->num($status->filledAmount)];
    }

    private function closeExecutionIfUnwound(int $executionId): void
    {
        $hasOpen = BotSellOrder::where('bot_buy_execution_id', $executionId)
            ->where('status', BotSellOrder::STATUS_OPEN)
            ->exists();
        if ($hasOpen) {
            return;
        }

        BotBuyExecution::whereKey($executionId)
            ->where('status', BotBuyExecution::STATUS_BOUGHT)
            ->update(['status' => BotBuyExecution::STATUS_CLOSED]);
    }

    private function cancelOrderIfEmpty(int $orderId): void
    {
        $hasOpen = BotSellOrder::query()
            ->join('bot_buy_executions', 'bot_buy_executions.id', '=', 'bot_sell_orders.bot_buy_execution_id')
            ->where('bot_buy_executions.bot_order_id', $orderId)
            ->where('bot_sell_orders.status', BotSellOrder::STATUS_OPEN)
            ->exists();

        $inFlight = BotBuyExecution::where('bot_order_id', $orderId)
            ->where(function ($q) {
                $q->whereIn('status', [BotBuyExecution::STATUS_PENDING, BotBuyExecution::STATUS_BUYING])
                    ->orWhere(fn ($b) => $b->where('status', BotBuyExecution::STATUS_BOUGHT)->whereDoesntHave('sellOrders'));
            })
            ->exists();

        if ($hasOpen || $inFlight) {
            return;
        }

        BotOrder::whereKey($orderId)
            ->where('status', '!=', 'CANCELED')
            ->update([
                'status'        => 'CANCELED',
                'cancel_source' => BotOrder::CANCEL_SOURCE_ADMIN,
                'completed_at'  => now(),
            ]);
    }

    /**
     * @return Collection<int,BotSellOrder>
     */
    private function openSells(int $currencyId): Collection
    {
        return BotSellOrder::query()
            ->where('status', BotSellOrder::STATUS_OPEN)
            ->whereHas('botBuyExecution', fn ($q) => $q->where('currency_id', $currencyId))
            ->with(['botBuyExecution.currency', 'botBuyExecution.botOrder.user'])
            ->orderBy('id')
            ->get();
    }

    /**
     * @return array{buys_in_flight:int, sells_pending:int}
     */
    private function inFlight(int $currencyId): array
    {
        return [
            'buys_in_flight' => BotBuyExecution::where('currency_id', $currencyId)
                ->whereIn('status', [BotBuyExecution::STATUS_PENDING, BotBuyExecution::STATUS_BUYING])
                ->count(),
            // BOUGHT but OpenSellOrdersJob hasn't fanned out tiers yet — the
            // held coin would be invisible to this run.
            'sells_pending' => BotBuyExecution::where('currency_id', $currencyId)
                ->where('status', BotBuyExecution::STATUS_BOUGHT)
                ->whereDoesntHave('sellOrders')
                ->count(),
        ];
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
            Log::channel('smart-bot')->warning('bot.currency_cancel.no_live_price', [
                'currency' => $currency->symbol,
                'error'    => $e->getMessage(),
            ]);

            return '0';
        }
    }

    /**
     * @return array{amount:string, principal:string, cost_basis:string, current_value:string, gross_pnl:string, performance_fee:string, refund:string}
     */
    private function emptyTotals(): array
    {
        return [
            'amount'          => '0',
            'principal'       => '0',
            'cost_basis'      => '0',
            'current_value'   => '0',
            'gross_pnl'       => '0',
            'performance_fee' => '0',
            'refund'          => '0',
        ];
    }

    /**
     * @param array<string,mixed> $bucket
     * @param array<string,string> $b
     */
    private function accumulate(array &$bucket, string $amount, array $b): void
    {
        $bucket['amount'] = bcadd($bucket['amount'], $amount, self::SCALE);
        foreach (['principal', 'cost_basis', 'current_value', 'gross_pnl', 'performance_fee', 'refund'] as $key) {
            $bucket[$key] = bcadd($bucket[$key], $b[$key], self::SCALE);
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

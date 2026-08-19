<?php

namespace App\Services\Bot;

use App\Models\Bot\BotBuyExecution;
use App\Models\Bot\BotGlobalSettings;
use App\Models\Bot\BotOrder;
use App\Models\Bot\BotSellOrder;
use App\Models\Currency;
use App\Models\CurrencyChain;
use App\Services\Bot\ReferenceExchange\ExchangeContract;
use App\Services\Bot\ReferenceExchange\ExchangeOrderResult;
use App\Services\Bot\ReferenceExchange\ExchangePositionCloser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Cancel preview + cancel-execution for a bot order.
 *
 * On cancel we MARKET-SELL every coin attached to OPEN sell orders on the
 * reference exchange (only if `bot_global_settings.cancel_sell_on_exchange_enabled`
 * is true; otherwise we just write settlement rows based on the live price).
 *
 * Per-item fees:
 *   - network_fee_usdt    = (chain.network_fee + chain.exchange_withdrawal_fee) * fill_price
 *                           chosen chain = withdraw-enabled chain with min total_withdrawal_fee.
 *   - performance_fee_usdt = max(0, current_value - cost_basis - network_fee) * performance_fee_percent / 100
 *   - exchange_fee_usdt    = realised on the market-sell (0 if disabled).
 *
 * total_fee   = network + exchange + performance
 * refund_usdt = max(0, gross_revenue - total_fee)
 */
class BotOrderCancelService
{
    private const SCALE = 8;

    /**
     * Coerce a mixed value into a bcmath-safe decimal string.
     * Handles null, empty strings, and scientific notation (e.g. "1.0E-7").
     */
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

    public function __construct(
        private readonly SettlementService $settlement,
        private readonly FeeCalculator $feeCalculator,
        private readonly ExchangeContract $exchange,
        private readonly PriceFeed $priceFeed,
    ) {}

    /**
     * @return array{
     *     order_id:int,
     *     open_sell_orders:int,
     *     sell_on_exchange:bool,
     *     remaining_cost_basis:string,
     *     total_current_value:string,
     *     total_network_fee:string,
     *     total_exchange_fee:string,
     *     total_performance_fee:string,
     *     total_fee:string,
     *     refund_to_balance:string,
     *     items:array<int,array<string,mixed>>
     * }
     */
    public function preview(BotOrder $order): array
    {
        $settings   = BotGlobalSettings::current();
        $perfPct    = $this->num($settings->performance_fee_percent);
        $sellOnExch = (bool) $settings->cancel_sell_on_exchange_enabled;

        [$openSells, $remainingCost] = $this->collectOpen($order);

        $items             = [];
        $totalCurrentValue = '0';
        $totalNetworkFee   = '0';
        $totalExchangeFee  = '0';
        $totalPerfFee      = '0';

        foreach ($openSells as $so) {
            $execution = $so->botBuyExecution;
            $currency  = $execution?->currency;
            $amount    = $this->num($so->amount_to_sell);
            $avgBuy    = $this->num($execution?->avg_buy_price);
            $cost      = bcmul($amount, $avgBuy, self::SCALE);

            $currentPrice = $this->currentPriceFor($currency?->symbol);
            $currentValue = bcmul($amount, $currentPrice, self::SCALE);

            $chain   = $this->pickChain($currency?->id);
            $feeCoin = $chain
                ? bcadd($this->num($chain->network_fee), $this->num($chain->exchange_withdrawal_fee), self::SCALE)
                : '0';
            $networkFeeUsdt = bcmul($feeCoin, $currentPrice, self::SCALE);

            // Buy-side ref-exchange fee share for this tier (USDT terms). The
            // settlement (SettlementService::allocatedBuyExchangeFee) WILL
            // charge this proportionally on cancel, so the preview must show
            // it too. The sell-side market fee is unknown until execution and
            // is intentionally not estimated here.
            $buyFeeShare = $this->allocatedBuyFeeShare($execution, $amount);

            // Formula components exposed so the UI can show a full breakdown.
            $buyFeeTotal = $this->num($execution?->buy_ref_exchange_fee);
            $totalFilled = $this->num($execution?->filled_amount);
            $sharePct    = bccomp($totalFilled, '0', self::SCALE) > 0
                ? bcmul(bcdiv($amount, $totalFilled, self::SCALE), '100', 4)
                : '0';

            $grossPnl     = bcsub($currentValue, $cost, self::SCALE);
            $pnlAfterFees = bcsub(bcsub($grossPnl, $networkFeeUsdt, self::SCALE), $buyFeeShare, self::SCALE);
            $perfFee      = '0';
            if (bccomp($pnlAfterFees, '0', self::SCALE) > 0) {
                $perfFee = bcdiv(bcmul($pnlAfterFees, $perfPct, self::SCALE), '100', self::SCALE);
            }

            $itemFee = bcadd(bcadd($networkFeeUsdt, $buyFeeShare, self::SCALE), $perfFee, self::SCALE);
            $refund  = bcsub($currentValue, $itemFee, self::SCALE);
            if (bccomp($refund, '0', self::SCALE) < 0) {
                $refund = '0';
            }

            $items[] = [
                'sell_order_id'   => $so->id,
                'currency_symbol' => (string) ($currency?->symbol ?? '—'),
                'currency_name'   => $currency?->persian_name ?? $currency?->name,
                'currency_logo'   => $currency?->logo ? config('bitexroom.currency_logo_base_url') . '/' . $currency->logo : null,
                'amount_to_sell'  => $amount,
                'avg_buy_price'   => $avgBuy,
                'current_price'   => $currentPrice,
                'cost_basis'      => $cost,
                'current_value'   => $currentValue,
                'gross_pnl'       => $grossPnl,
                'is_profitable'   => bccomp($pnlAfterFees, '0', self::SCALE) > 0,
                'network_fee'       => $networkFeeUsdt,
                'network_fee_coin'  => $feeCoin,
                'exchange_fee'      => $buyFeeShare,
                'buy_fee_total'     => $buyFeeTotal,
                'buy_fee_share_pct' => $sharePct,
                'performance_fee'   => $perfFee,
                'total_fee'         => $itemFee,
                'refund'            => $refund,
                'chain'             => $chain?->chain instanceof \BackedEnum ? $chain->chain->value : (string) ($chain?->chain ?? ''),
            ];

            $totalCurrentValue = bcadd($totalCurrentValue, $currentValue, self::SCALE);
            $totalNetworkFee   = bcadd($totalNetworkFee, $networkFeeUsdt, self::SCALE);
            $totalExchangeFee  = bcadd($totalExchangeFee, $buyFeeShare, self::SCALE);
            $totalPerfFee      = bcadd($totalPerfFee, $perfFee, self::SCALE);
        }

        $totalFee = bcadd(bcadd($totalNetworkFee, $totalExchangeFee, self::SCALE), $totalPerfFee, self::SCALE);
        $refund   = bcsub($totalCurrentValue, $totalFee, self::SCALE);
        if (bccomp($refund, '0', self::SCALE) < 0) {
            $refund = '0';
        }

        return [
            'order_id'                => $order->id,
            'open_sell_orders'        => $openSells->count(),
            'sell_on_exchange'        => $sellOnExch,
            'performance_fee_percent' => $perfPct,
            'remaining_cost_basis'  => $remainingCost,
            'total_current_value'   => $totalCurrentValue,
            'total_network_fee'     => $totalNetworkFee,
            'total_exchange_fee'    => $totalExchangeFee,
            'total_performance_fee' => $totalPerfFee,
            'total_fee'             => $totalFee,
            'refund_to_balance'     => $refund,
            'items'                 => $items,
        ];
    }

    /**
     * @return array{order_id:int, canceled:int, total_fee:string, total_refund:string}
     */
    public function cancel(BotOrder $order, string $source = BotOrder::CANCEL_SOURCE_USER): array
    {
        $source = $source === BotOrder::CANCEL_SOURCE_ADMIN
            ? BotOrder::CANCEL_SOURCE_ADMIN
            : BotOrder::CANCEL_SOURCE_USER;
        $cancelReason = $source === BotOrder::CANCEL_SOURCE_ADMIN
            ? BotSellOrder::CANCEL_ADMIN
            : BotSellOrder::CANCEL_USER;

        $settings   = BotGlobalSettings::current();
        $perfPct    = $this->num($settings->performance_fee_percent);
        $sellOnExch = (bool) $settings->cancel_sell_on_exchange_enabled;

        return DB::transaction(function () use ($order, $perfPct, $sellOnExch, $source, $cancelReason) {
            [$openSells] = $this->collectOpen($order);

            $totalFee    = '0';
            $totalRefund = '0';
            $canceled    = 0;

            foreach ($openSells as $sellOrder) {
                $currency = $sellOrder->botBuyExecution?->currency;
                $amount   = $this->num($sellOrder->amount_to_sell);

                // 1) cancel the OPEN limit-sell on the reference exchange (best-effort)
                $this->cancelOnExchange($sellOrder);

                // 2) liquidate the held coin (or skip if disabled)
                if ($sellOnExch && $currency) {
                    $result    = $this->marketSell($sellOrder, $currency->symbol, $amount);
                    $fillPrice = $result && $result->isFilled() ? $this->num($result->avgPrice) : $this->currentPriceFor($currency->symbol);
                    $exchFee   = $result && $result->isFilled() ? $this->num($result->exchangeFee) : '0';
                } else {
                    $fillPrice = $this->currentPriceFor($currency?->symbol);
                    $exchFee   = '0';
                }

                $chain   = $this->pickChain($currency?->id);
                $feeCoin = $chain
                    ? bcadd($this->num($chain->network_fee), $this->num($chain->exchange_withdrawal_fee), self::SCALE)
                    : '0';
                $networkFeeUsdt = bcmul($feeCoin, $fillPrice, self::SCALE);

                if (bccomp($exchFee, '0', 8) > 0) {
                    $sellOrder->update(['sell_ref_exchange_fee' => $exchFee]);
                }

                $settlement = $this->settlement->settleCancelMarketSell(
                    sellOrder:          $sellOrder,
                    filledAmount:       $amount,
                    fillPrice:          $fillPrice,
                    networkFee:         $networkFeeUsdt,
                    sellRefExchangeFee: $exchFee,
                    perfFeePercent:     $perfPct,
                    cancelReason:       $cancelReason,
                );

                $itemFee = bcadd(
                    bcadd($networkFeeUsdt, $exchFee, self::SCALE),
                    $this->num($settlement->performance_fee),
                    self::SCALE
                );
                $totalFee    = bcadd($totalFee, $itemFee, self::SCALE);
                $itemRefund  = bcsub($this->num($settlement->gross_revenue), $itemFee, self::SCALE);
                if (bccomp($itemRefund, '0', self::SCALE) < 0) {
                    $itemRefund = '0';
                }
                $totalRefund = bcadd($totalRefund, $itemRefund, self::SCALE);
                $canceled++;
            }

            $order->update([
                'status'        => 'CANCELED',
                'cancel_source' => $source,
                'completed_at'  => now(),
            ]);

            $this->closeBoughtExecutions($order);

            return [
                'order_id'     => $order->id,
                'canceled'     => $canceled,
                'total_fee'    => $totalFee,
                'total_refund' => $totalRefund,
            ];
        });
    }

    /**
     * A bought execution with no remaining OPEN sells is no longer a live
     * position — mark it CLOSED so the UI doesn't look like we still hold it.
     */
    private function closeBoughtExecutions(BotOrder $order): void
    {
        $boughtIds = BotBuyExecution::query()
            ->where('bot_order_id', $order->id)
            ->where('status', BotBuyExecution::STATUS_BOUGHT)
            ->pluck('id');

        foreach ($boughtIds as $executionId) {
            $hasOpen = BotSellOrder::query()
                ->where('bot_buy_execution_id', $executionId)
                ->where('status', BotSellOrder::STATUS_OPEN)
                ->exists();
            if ($hasOpen) {
                continue;
            }

            $wasUnwound = BotSellOrder::query()
                ->where('bot_buy_execution_id', $executionId)
                ->where('status', BotSellOrder::STATUS_CANCELED)
                ->exists();
            if (! $wasUnwound) {
                continue;
            }

            BotBuyExecution::where('id', $executionId)
                ->where('status', BotBuyExecution::STATUS_BOUGHT)
                ->update(['status' => BotBuyExecution::STATUS_CLOSED]);
        }
    }

    /**
     * Proportional share of the execution's buy-side ref-exchange fee that
     * this tier will carry at settlement (USDT terms). Mirrors
     * SettlementService::allocatedBuyExchangeFee so the preview matches what
     * the cancel actually charges. Note: buy_ref_exchange_fee is stored in
     * USDT even when the exchange charged it in the base coin
     * (buy_ref_exchange_fee_currency), so no conversion is needed here.
     */
    private function allocatedBuyFeeShare(?\App\Models\Bot\BotBuyExecution $execution, string $amount): string
    {
        $totalBuyFee = $this->num($execution?->buy_ref_exchange_fee);
        $totalFilled = $this->num($execution?->filled_amount);

        if (
            bccomp($totalBuyFee, '0', self::SCALE) <= 0 ||
            bccomp($totalFilled, '0', self::SCALE) <= 0 ||
            bccomp($amount, '0', self::SCALE) <= 0
        ) {
            return '0';
        }

        return bcdiv(bcmul($totalBuyFee, $amount, self::SCALE), $totalFilled, self::SCALE);
    }

    /**
     * @return array{0:\Illuminate\Support\Collection<int,BotSellOrder>, 1:string}
     */
    private function collectOpen(BotOrder $order): array
    {
        $openSells = BotSellOrder::query()
            ->whereIn('bot_buy_execution_id', $order->buyExecutions()->pluck('id'))
            ->where('status', BotSellOrder::STATUS_OPEN)
            ->with(['botBuyExecution', 'botBuyExecution.currency'])
            ->get();

        $remaining = '0';
        foreach ($openSells as $so) {
            $cost = bcmul($this->num($so->amount_to_sell), $this->num($so->botBuyExecution?->avg_buy_price), self::SCALE);
            $remaining = bcadd($remaining, $cost, self::SCALE);
        }

        return [$openSells, $remaining];
    }

    private function cancelOnExchange(BotSellOrder $sellOrder): void
    {
        if (! $sellOrder->exchange_order_id) {
            return;
        }
        $currency = $sellOrder->botBuyExecution?->currency;
        if (! $currency) {
            return;
        }
        $market = strtoupper((string) $currency->symbol).'USDT';
        try {
            $this->exchange->cancelOrder($market, (string) $sellOrder->exchange_order_id);
        } catch (\Throwable $e) {
            Log::warning('bot.cancel.exchange_failed', [
                'sell_order_id'     => $sellOrder->id,
                'exchange_order_id' => $sellOrder->exchange_order_id,
                'market'            => $market,
                'error'             => $e->getMessage(),
            ]);
        }
    }

    private function marketSell(BotSellOrder $sellOrder, string $symbol, string $amount): ?ExchangeOrderResult
    {
        $market = strtoupper($symbol).'USDT';
        try {
            $execution = $sellOrder->botBuyExecution;
            $baseFee   = $execution
                ? ExchangePositionCloser::baseFeeCoinFromExecution($execution)
                : '0';

            // baseFee is only applied on a failed first attempt (insufficient
            // balance when fee was charged in base and amount was still gross).
            return app(ExchangePositionCloser::class)->marketSell($market, $amount, $baseFee);
        } catch (\Throwable $e) {
            Log::warning('bot.cancel.market_sell_failed', [
                'market' => $market,
                'amount' => $amount,
                'error'  => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Pick the withdraw-enabled chain with the lowest total fee.
     */
    private function pickChain(?int $currencyId): ?CurrencyChain
    {
        if (! $currencyId) {
            return null;
        }
        $chains = CurrencyChain::query()
            ->where('currency_id', $currencyId)
            ->where('withdraw_enabled', true)
            ->get();

        if ($chains->isEmpty()) {
            return CurrencyChain::query()->where('currency_id', $currencyId)->first();
        }

        return $chains
            ->sortBy(fn ($c) => (float) ((string) $c->network_fee) + (float) ((string) $c->exchange_withdrawal_fee))
            ->first();
    }

    private function currentPriceFor(?string $symbol): string
    {
        if (! $symbol) {
            return '0';
        }
        $currencyId = Currency::where('symbol', $symbol)->value('id');
        if (! $currencyId) {
            return '0';
        }
        try {
            $price = $this->num($this->priceFeed->getLive($currencyId));
            if (bccomp($price, '0', self::SCALE) > 0) {
                return $price;
            }
        } catch (\Throwable $e) {
            Log::debug('bot.cancel.price_fallback', ['symbol' => $symbol, 'error' => $e->getMessage()]);
        }
        return '0';
    }
}

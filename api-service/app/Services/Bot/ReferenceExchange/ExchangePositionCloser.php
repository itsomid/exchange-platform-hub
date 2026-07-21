<?php

namespace App\Services\Bot\ReferenceExchange;

use App\Models\Bot\BotBuyExecution;

/**
 * Best-effort market-sell of a base position on the reference exchange.
 *
 * When the buy fee was charged in the base coin, CoinEx's available balance is
 * gross_fill − base_fee. If a caller still passes a gross amount (legacy rows,
 * or amount_to_sell sized before fee netting), the first sell fails and we
 * retry once with the base fee subtracted.
 */
final class ExchangePositionCloser
{
    private const SCALE = 8;

    public function __construct(private readonly ExchangeContract $exchange) {}

    public function marketSell(string $market, string $amount, string $baseFeeCoin = '0'): ExchangeOrderResult
    {
        if (bccomp($amount, '0', self::SCALE) <= 0) {
            return new ExchangeOrderResult(
                exchangeOrderId: null,
                status:          ExchangeOrderStatus::FAILED,
                errorCode:       'ZERO_AMOUNT',
                errorMessage:    'nothing to sell',
            );
        }

        $result = $this->exchange->placeMarketSell($market, $amount);
        if ($result->exchangeOrderId !== null) {
            return $result;
        }

        if (bccomp($baseFeeCoin, '0', self::SCALE) <= 0) {
            return $result;
        }

        $adjusted = bcsub($amount, $baseFeeCoin, self::SCALE);
        if (bccomp($adjusted, '0', self::SCALE) <= 0 || bccomp($adjusted, $amount, self::SCALE) >= 0) {
            return $result;
        }

        return $this->exchange->placeMarketSell($market, $adjusted);
    }

    /**
     * Base-coin fee quantity implied by buy_ref_exchange_fee (USDT) when the
     * fee currency matches the bought symbol. Zero when fee was in quote.
     */
    public static function baseFeeCoinFromExecution(BotBuyExecution $execution): string
    {
        $symbol = strtoupper((string) ($execution->currency?->symbol ?? ''));
        $feeCur = strtoupper((string) ($execution->buy_ref_exchange_fee_currency ?? ''));
        if ($symbol === '' || $feeCur === '' || $feeCur !== $symbol) {
            return '0';
        }

        $feeUsdt = (string) ($execution->buy_ref_exchange_fee ?? '0');
        $avg     = (string) ($execution->avg_buy_price ?? '0');
        if (bccomp($feeUsdt, '0', self::SCALE) <= 0 || bccomp($avg, '0', self::SCALE) <= 0) {
            return '0';
        }

        return bcdiv($feeUsdt, $avg, self::SCALE);
    }
}

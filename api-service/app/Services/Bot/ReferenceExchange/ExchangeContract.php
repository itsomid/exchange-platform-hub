<?php

namespace App\Services\Bot\ReferenceExchange;

/**
 * Contract for the reference exchange (CoinEx) used by the auto-trade bot.
 * Implementations target the omnibus model: a single API key for the platform,
 * not per-user keys.
 *
 * All `$market` values are CoinEx-style upper-case pair symbols, e.g. "BTCUSDT".
 * All numeric strings are BCMath-friendly (scale 8).
 */
interface ExchangeContract
{
    /**
     * Place a spot MARKET BUY paying $quoteAmount of the quote currency (USDT).
     * Returns FILLED on success with the realized fill data.
     */
    public function placeMarketBuy(string $market, string $quoteAmount): ExchangeOrderResult;

    /**
     * Place a spot LIMIT SELL of $baseAmount of the base currency at exact $price.
     * Returns OPEN on success; the order sits in CoinEx orderbook until filled.
     */
    public function placeLimitSell(string $market, string $baseAmount, string $price): ExchangeOrderResult;

    /**
     * Place a spot MARKET SELL of $baseAmount of the base currency.
     * Returns FILLED on success with realized fill data. Used as the
     * emergency-liquidation fallback when the normal tiered sell path can
     * not be opened (e.g. amount below p2p minimum) so the coin doesn't
     * get stranded on the omnibus exchange account.
     */
    public function placeMarketSell(string $market, string $baseAmount): ExchangeOrderResult;

    /**
     * Look up an existing order's current state by id.
     */
    public function getOrder(string $market, string $exchangeOrderId): ExchangeOrderResult;

    /**
     * Best-effort cancel. Implementations should swallow "already filled / not found"
     * errors and throw only on hard failures (auth, network).
     */
    public function cancelOrder(string $market, string $exchangeOrderId): void;
}

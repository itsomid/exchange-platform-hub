<?php

namespace App\Services\Bot;

use App\Models\Currency;
use App\Models\Market;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

/**
 * Provides the current "live" price (USDT) for a given currency.
 *
 * Strategy:
 *   1. Socket cache (Redis key written by the price websocket worker).
 *   2. Fallback to exchange_prices table via the base market.
 */
class PriceFeed
{
    /**
     * @throws RuntimeException when no price is available from either source.
     */
    public function getLive(int $currencyId): float
    {
        $currency = Currency::find($currencyId);
        if (! $currency) {
            throw new RuntimeException("Currency {$currencyId} not found");
        }

        $cached = Cache::get("market:price:{$currency->symbol}USDT");
        if ($cached !== null && is_numeric($cached) && (float) $cached > 0) {
            return (float) $cached;
        }

        $market = Market::where('base_currency', $currency->symbol)
            ->where('quote_currency', 'USDT')
            ->with('exchangePrice')
            ->first();

        if ($market && $market->exchangePrice && (float) $market->exchangePrice->price > 0) {
            return (float) $market->exchangePrice->price;
        }

        throw new RuntimeException("No live price for currency {$currency->symbol}");
    }
}

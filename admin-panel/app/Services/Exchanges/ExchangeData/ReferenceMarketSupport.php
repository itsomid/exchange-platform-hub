<?php

namespace App\Services\Exchanges\ExchangeData;

use App\Models\Exchange;
use App\Models\Market;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class ReferenceMarketSupport
{
    public bool $checkFailed = false;

    /**
     * Trading pairs on the reference exchange, keyed by symbol (e.g. BTCUSDT).
     * Null when this exchange has no data adapter, or the request failed.
     *
     * @return array<string, array<string, mixed>>|null
     */
    public function tradingMarkets(string $slug): ?array
    {
        try {
            ExchangeDataFactory::make($slug);
        } catch (InvalidArgumentException) {
            return null;
        }

        try {
            return Cache::remember(
                'reference-market-support:' . $slug,
                300,
                function () use ($slug) {
                    $keyed = [];
                    foreach (ExchangeDataFactory::make($slug)->fetchMinTrade() as $row) {
                        $symbol = strtoupper((string) ($row['market'] ?? ''));
                        if ($symbol !== '') {
                            $keyed[$symbol] = $row;
                        }
                    }

                    if ($keyed === []) {
                        throw new RuntimeException('Reference exchange returned no trading markets.');
                    }

                    return $keyed;
                }
            );
        } catch (Throwable) {
            $this->checkFailed = true;

            return null;
        }
    }

    /**
     * Markets whose selected reference exchange does not list the pair.
     *
     * @param  Collection<int, Market>  $markets
     * @return Collection<int, array{market: Market, exchange: Exchange, symbol: string}>
     */
    public function unsupported(Collection $markets): Collection
    {
        $bySlug = [];

        return $markets->map(function (Market $market) use (&$bySlug) {
            $exchange = $market->activeExchangePrice?->exchange;
            if (!$exchange) {
                return null;
            }

            if (!array_key_exists($exchange->slug, $bySlug)) {
                $bySlug[$exchange->slug] = $this->tradingMarkets($exchange->slug);
            }

            $listed = $bySlug[$exchange->slug];
            if ($listed === null) {
                return null;
            }

            $symbol = strtoupper($market->base_currency . $market->quote_currency);
            if (isset($listed[$symbol])) {
                return null;
            }

            return [
                'market' => $market,
                'exchange' => $exchange,
                'symbol' => $market->base_currency . '/' . $market->quote_currency,
            ];
        })->filter()->values();
    }
}

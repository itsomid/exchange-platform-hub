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

    /** @var Collection<int, array{market: Market, exchange: Exchange, symbol: string}> */
    public Collection $unreachable;

    /** @var array<string, string> */
    private array $slugState = [];

    public function __construct()
    {
        $this->unreachable = collect();
    }

    /**
     * Trading pairs on the reference exchange, keyed by symbol (e.g. BTCUSDT).
     * Null when this exchange has no data adapter, or the request failed.
     *
     * Pass $symbols to download only those pairs. The full catalog is large
     * enough to exhaust the web memory limit.
     *
     * @param  list<string>|null  $symbols
     * @return array<string, array<string, mixed>>|null
     */
    public function tradingMarkets(string $slug, ?array $symbols = null): ?array
    {
        try {
            ExchangeDataFactory::make($slug);
        } catch (InvalidArgumentException) {
            $this->slugState[$slug] = 'unavailable';

            return null;
        }

        try {
            $listed = Cache::remember(
                $this->cacheKey($slug, $symbols),
                300,
                function () use ($slug, $symbols) {
                    $keyed = [];
                    foreach (ExchangeDataFactory::make($slug)->fetchMinTrade($symbols) as $row) {
                        $symbol = strtoupper((string) ($row['market'] ?? ''));
                        if ($symbol !== '') {
                            $keyed[$symbol] = $row;
                        }
                    }

                    if ($keyed === [] && $symbols === null) {
                        throw new RuntimeException('Reference exchange returned no trading markets.');
                    }

                    return $keyed;
                }
            );
            $this->slugState[$slug] = 'ok';

            return $listed;
        } catch (Throwable) {
            $this->slugState[$slug] = 'failed';
            $this->checkFailed = true;

            return null;
        }
    }

    /**
     * Markets whose selected reference exchange does not list the pair.
     * Markets that could not be checked are collected on $unreachable.
     *
     * @param  Collection<int, Market>  $markets
     * @return Collection<int, array{market: Market, exchange: Exchange, symbol: string}>
     */
    public function unsupported(Collection $markets): Collection
    {
        $this->unreachable = collect();
        $symbolsBySlug = [];

        foreach ($markets as $market) {
            $exchange = $market->activeExchangePrice?->exchange;
            if (!$exchange) {
                continue;
            }

            $symbolsBySlug[$exchange->slug][] = strtoupper($market->base_currency . $market->quote_currency);
        }

        $bySlug = [];
        foreach ($symbolsBySlug as $slug => $symbols) {
            $bySlug[$slug] = $this->tradingMarkets($slug, array_values(array_unique($symbols)));
        }

        return $markets->map(function (Market $market) use ($bySlug) {
            $exchange = $market->activeExchangePrice?->exchange;
            if (!$exchange) {
                return null;
            }

            $listed = $bySlug[$exchange->slug] ?? null;
            $pair = $market->base_currency . '/' . $market->quote_currency;

            if ($listed === null) {
                if (($this->slugState[$exchange->slug] ?? null) === 'failed') {
                    $this->unreachable->push([
                        'market' => $market,
                        'exchange' => $exchange,
                        'symbol' => $pair,
                    ]);
                }

                return null;
            }

            $symbol = strtoupper($market->base_currency . $market->quote_currency);
            if (isset($listed[$symbol])) {
                return null;
            }

            return [
                'market' => $market,
                'exchange' => $exchange,
                'symbol' => $pair,
            ];
        })->filter()->values();
    }

    /**
     * @param  list<string>|null  $symbols
     */
    private function cacheKey(string $slug, ?array $symbols): string
    {
        $key = 'reference-market-support:' . $slug;
        if ($symbols === null) {
            return $key;
        }

        $symbols = array_map(fn ($symbol) => strtoupper((string) $symbol), $symbols);
        sort($symbols);

        return $key . ':' . md5(implode(',', $symbols));
    }
}

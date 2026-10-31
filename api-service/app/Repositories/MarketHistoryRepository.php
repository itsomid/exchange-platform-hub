<?php

namespace App\Repositories;

use App\Models\Market;
use App\Models\MarketHistory;
use App\Repositories\Interfaces\MarketHistoryRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class MarketHistoryRepository implements MarketHistoryRepositoryInterface
{
    /**
     * Cache TTL in seconds (5 minutes, aligned with fetch:market-history schedule).
     */
    private const SPARKLINE_CACHE_TTL_SECONDS = 300;

    public function getByMarketIdWithDate(int $marketId, Carbon $date): ?MarketHistory
    {
        return MarketHistory::query()->where('market_id', $marketId)
            ->whereDate('timestamp', $date)
            ->latest()
            ->first();
    }

    public function getByMarketIdWithDateTime(int $marketId, Carbon $dateTime): ?MarketHistory
    {
        $startOfHour = $dateTime->copy()->startOfHour();
        $endOfHour = $dateTime->copy()->endOfHour();

        return MarketHistory::query()->where('market_id', $marketId)
            ->whereBetween('timestamp', [$startOfHour, $endOfHour])
            ->latest('timestamp')
            ->first();
    }

    public function getWeeklySparklines(array $symbols = [], int $maxPoints = 168): array
    {
        $maxPoints = max(2, $maxPoints);

        // Cache active markets mapping (id => symbol) for 60 seconds
        $activeMarkets = Cache::remember(__CLASS__ . ':activeMarketsMap', 60, function (): array {
            return Market::query()
                ->where('is_active', true)
                ->pluck('base_currency', 'id')
                ->map(fn (string $symbol) => strtoupper(trim($symbol)))
                ->all();
        });

        if ($activeMarkets === []) {
            return [];
        }

        // Normalize requested symbols if provided
        $requestedSymbols = array_values(array_unique(array_filter(array_map(
            fn ($symbol) => strtoupper(trim((string) $symbol)),
            $symbols
        ))));

        if ($requestedSymbols !== []) {
            $requestedSet = array_flip($requestedSymbols);
            $targetMarkets = array_filter(
                $activeMarkets,
                fn (string $symbol) => isset($requestedSet[$symbol])
            );
        } else {
            $targetMarkets = $activeMarkets;
        }

        if ($targetMarkets === []) {
            $emptyResult = [];
            foreach ($requestedSymbols as $symbol) {
                $emptyResult[$symbol] = [];
            }

            return $emptyResult;
        }

        // Build per-symbol cache keys and fetch all in a single Redis MGET (Cache::many)
        $cacheKeysBySymbol = [];
        foreach ($targetMarkets as $symbol) {
            $cacheKeysBySymbol[$symbol] = $this->sparklineCacheKey($symbol, $maxPoints);
        }

        $cachedValues = Cache::many(array_values($cacheKeysBySymbol));

        $result = [];
        $missingMarkets = []; // market_id => symbol

        foreach ($targetMarkets as $marketId => $symbol) {
            $cacheKey = $cacheKeysBySymbol[$symbol];
            if (is_array($cachedValues[$cacheKey] ?? null)) {
                $result[$symbol] = $cachedValues[$cacheKey];
            } else {
                $result[$symbol] = [];
                $missingMarkets[(int) $marketId] = $symbol;
            }
        }

        if ($missingMarkets === []) {
            return $result;
        }

        // Query latest timestamp per missing market so each coin uses its own 7-day window
        $latestTimestamps = MarketHistory::query()
            ->toBase()
            ->selectRaw('market_id, MAX(timestamp) as max_timestamp')
            ->whereIn('market_id', array_keys($missingMarkets))
            ->groupBy('market_id')
            ->pluck('max_timestamp', 'market_id');

        if ($latestTimestamps->isNotEmpty()) {
            $cutoffs = [];
            foreach ($latestTimestamps as $marketId => $maxTs) {
                if ($maxTs === null) {
                    continue;
                }
                $cutoffs[(int) $marketId] = is_numeric($maxTs)
                    ? Carbon::createFromTimestamp((int) $maxTs)->subDays(7)->timestamp
                    : Carbon::parse((string) $maxTs)->subDays(7)->toDateTimeString();
            }

            if ($cutoffs !== []) {
                $minCutoff = min($cutoffs);

                // Stream rows ordered by (market_id, timestamp) to leverage the composite unique index
                $rows = MarketHistory::query()
                    ->toBase()
                    ->select(['market_id', 'close', 'timestamp'])
                    ->whereIn('market_id', array_keys($cutoffs))
                    ->where('timestamp', '>=', $minCutoff)
                    ->orderBy('market_id')
                    ->orderBy('timestamp')
                    ->cursor();

                $currentMarketId = null;
                $buffer = [];

                foreach ($rows as $row) {
                    $marketId = (int) $row->market_id;

                    if ($currentMarketId !== null && $marketId !== $currentMarketId) {
                        $symbol = $missingMarkets[$currentMarketId] ?? null;
                        if ($symbol !== null) {
                            $result[$symbol] = $this->downsampleSeries($buffer, $maxPoints);
                        }
                        $buffer = [];
                    }

                    $currentMarketId = $marketId;

                    if (isset($cutoffs[$marketId]) && $row->timestamp >= $cutoffs[$marketId]) {
                        $buffer[] = (float) $row->close;
                    }
                }

                if ($currentMarketId !== null && $buffer !== []) {
                    $symbol = $missingMarkets[$currentMarketId] ?? null;
                    if ($symbol !== null) {
                        $result[$symbol] = $this->downsampleSeries($buffer, $maxPoints);
                    }
                }
            }
        }

        // Store newly computed non-empty series per symbol in cache (single Redis MSET via Cache::putMany)
        $toCache = [];
        foreach ($missingMarkets as $symbol) {
            if (! empty($result[$symbol])) {
                $toCache[$cacheKeysBySymbol[$symbol]] = $result[$symbol];
            }
        }

        if ($toCache !== []) {
            Cache::putMany($toCache, self::SPARKLINE_CACHE_TTL_SECONDS);
        }

        return $result;
    }

    private function sparklineCacheKey(string $symbol, int $maxPoints): string
    {
        return __CLASS__ . ":sparkline:v3:{$maxPoints}:{$symbol}";
    }

    /**
     * Downsample 5-minute data (up to 2,016 points per 7 days) to at most $maxPoints
     * while preserving exact first and last prices.
     *
     * @param  list<float>  $prices
     * @return list<float>
     */
    private function downsampleSeries(array $prices, int $maxPoints): array
    {
        $count = count($prices);

        if ($count === 0) {
            return [];
        }

        if ($count === 1) {
            return [$prices[0], $prices[0]];
        }

        if ($count <= $maxPoints) {
            return $prices;
        }

        $sampled = [];
        $lastIndex = $count - 1;
        $step = $lastIndex / ($maxPoints - 1);

        for ($i = 0; $i < $maxPoints - 1; $i++) {
            $sampled[] = $prices[(int) round($i * $step)];
        }

        $sampled[] = $prices[$lastIndex];

        return $sampled;
    }
}

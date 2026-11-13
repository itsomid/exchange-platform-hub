<?php

namespace App\Http\Controllers\V1\Market;

use App\Models\Market;
use App\Repositories\Interfaces\MarketHistoryRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class HeroMarketController
{
    private const CACHE_SECONDS = 1800;

    private const COIN_LIMIT = 28;

    private const SPARKLINE_POINTS = 48;

    private const PREFERRED_SYMBOLS = [
        'BTC', 'ETH', 'BNB', 'SOL', 'XRP', 'DOGE', 'ADA', 'AVAX', 'DOT', 'LINK',
        'LTC', 'TRX', 'SHIB', 'UNI', 'ATOM', 'BCH', 'NEAR', 'AAVE', 'FIL', 'ETC',
        'XLM', 'ARB', 'OP', 'INJ', 'SUI', 'PEPE', 'POL', 'GRT',
    ];

    public function __construct(
        private readonly MarketHistoryRepositoryInterface $marketHistoryRepository,
    ) {}

    public function __invoke(): JsonResponse
    {
        $cached = Cache::get('markets:hero:v1');
        if (is_array($cached) && $cached !== []) {
            return $this->respond($cached);
        }

        $payload = $this->build();
        if ($payload !== []) {
            Cache::put('markets:hero:v1', $payload, self::CACHE_SECONDS);
        }

        return $this->respond($payload);
    }

    private function respond(array $payload): JsonResponse
    {
        return response()
            ->json(['data' => $payload])
            ->header('Cache-Control', 'public, max-age='.self::CACHE_SECONDS);
    }

    private function build(): array
    {
        $markets = Market::query()
            ->where('is_active', true)
            ->where('quote_currency', 'USDT')
            ->with(['currency', 'exchangePrice'])
            ->get()
            ->unique('base_currency')
            ->keyBy(fn (Market $market) => strtoupper($market->base_currency));

        $ordered = collect(self::PREFERRED_SYMBOLS)
            ->filter(fn (string $symbol) => $markets->has($symbol))
            ->merge($markets->keys()->reject(fn (string $symbol) => in_array($symbol, self::PREFERRED_SYMBOLS, true)))
            ->take(self::COIN_LIMIT)
            ->values();

        $selected = $ordered
            ->map(fn (string $symbol) => $markets->get($symbol))
            ->filter();

        $sparklines = $selected->isEmpty()
            ? []
            : $this->marketHistoryRepository->getWeeklySparklines(
                $selected->map(fn (Market $market) => strtoupper($market->base_currency))->all(),
                self::SPARKLINE_POINTS,
            );

        $logoBase = rtrim((string) config('bitexroom.currency_logo_base_url'), '/');

        return $selected->map(function (Market $market) use ($sparklines, $logoBase): array {
            $symbol = strtoupper($market->base_currency);
            $exchangePrice = $market->exchangePrice;
            $lastPrice = $exchangePrice?->price;
            $openPrice = $exchangePrice?->open_price;
            $change = (! is_null($lastPrice) && ! is_null($openPrice) && (float) $openPrice > 0)
                ? round((((float) $lastPrice - (float) $openPrice) / (float) $openPrice) * 100, 2)
                : null;
            $logo = $market->currency?->logo;

            return [
                'symbol' => $symbol,
                'name' => $market->currency?->name,
                'persian_name' => $market->currency?->persian_name,
                'logo' => $logo ? $logoBase.'/'.$logo : null,
                'quote' => $market->quote_currency,
                'market_id' => $market->id,
                'price_precision' => $market->currency?->price_precision ?? 2,
                'buy_price' => $exchangePrice?->buy_price,
                'change' => $change,
                'sparkline' => array_map(
                    fn ($price) => round((float) $price, 8),
                    $sparklines[$symbol] ?? [],
                ),
            ];
        })->values()->all();
    }
}

<?php

namespace App\Http\Controllers\V1\Market;

use App\Models\Market;
use App\Models\MarketHistory;
use App\Models\SpotTrade;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class MarketOverviewController
{
    public function __invoke(): JsonResponse
    {
        $markets = Market::query()
            ->where('is_active', true)
            ->with(['currency', 'quoteCurrency', 'exchangePrice'])
            ->orderBy('base_currency')
            ->get();

        $marketIds = $markets->pluck('id');

        $latestHistoryQuery = MarketHistory::query()
            ->select('market_id', DB::raw('MAX(timestamp) as latest_timestamp'))
            ->whereIn('market_id', $marketIds)
            ->groupBy('market_id');

        $histories = MarketHistory::query()
            ->joinSub($latestHistoryQuery, 'latest_histories', function ($join) {
                $join->on('market_histories.market_id', '=', 'latest_histories.market_id')
                    ->on('market_histories.timestamp', '=', 'latest_histories.latest_timestamp');
            })
            ->select('market_histories.*')
            ->get()
            ->keyBy('market_id');

        $volumes = SpotTrade::query()
            ->select('market_id', DB::raw('SUM(quantity) as volume'))
            ->whereIn('market_id', $marketIds)
            ->where('created_at', '>=', now()->subHours(24))
            ->groupBy('market_id')
            ->pluck('volume', 'market_id');

        $items = $markets
            ->map(fn (Market $market) => $this->formatMarket(
                $market,
                $histories->get($market->id),
                $volumes->get($market->id, 0)
            ))
            ->values();

        return response()->json([
            'data' => [
                'gainers' => $items->sortByDesc('price_change_percentage')->take(5)->values(),
                'losers' => $items->sortBy('price_change_percentage')->take(5)->values(),
                'markets' => $items,
            ],
        ]);
    }

    private function formatMarket(Market $market, ?MarketHistory $history, mixed $volume): array
    {
        $price = $market->exchangePrice?->price;
        $open = $history?->open ?? $market->exchangePrice?->open_price ?? $price;
        $priceChangePercentage = 0.0;

        if (! is_null($price) && ! is_null($open) && (float) $open !== 0.0) {
            $priceChangePercentage = round((((float) $price - (float) $open) / (float) $open) * 100, 2);
        }

        return [
            'market_id' => $market->id,
            'pair' => $market->base_currency . '/' . $market->quote_currency,
            'base_currency' => $market->base_currency,
            'quote_currency' => $market->quote_currency,
            'currency_name' => $market->currency?->name,
            'currency_persian_name' => $market->currency?->persian_name,
            'currency_logo' => $this->logoUrl($market->currency?->logo),
            'quote_currency_logo' => $this->logoUrl($market->quoteCurrency?->logo),
            'price' => $price,
            'buy_price' => $market->exchangePrice?->buy_price,
            'sell_price' => $market->exchangePrice?->sell_price,
            'open' => $open,
            'high' => $history?->high ?? $price,
            'low' => $history?->low ?? $price,
            'volume_24h' => $volume,
            'price_change_amount' => ! is_null($price) && ! is_null($open) ? (string) ((float) $price - (float) $open) : '0',
            'price_change_percentage' => $priceChangePercentage,
            'price_precision' => $market->currency?->price_precision ?? 8,
            'amount_precision' => $market->currency?->amount_precision ?? 8,
            'quote_precision' => $market->quoteCurrency?->amount_precision ?? 2,
            'is_active' => $market->is_active,
        ];
    }

    private function logoUrl(?string $logo): ?string
    {
        return $logo ? config('bitexroom.currency_logo_base_url') . '/' . $logo : null;
    }
}
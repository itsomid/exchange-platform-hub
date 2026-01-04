<?php

namespace App\Services\Exchanges\MarketState;

use App\Models\MarketHistory;
use App\Models\SpotTrade;
use App\Repositories\Interfaces\MarketRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

readonly class MarketStateService
{
    public function __construct(private MarketRepositoryInterface $marketRepository) {}

    public function getState(int $marketId): array
    {
        $market = $this->marketRepository->getMarketById($marketId);
        if (is_null($market)) {
            throw new NotFoundHttpException;
        }
        $last = $market->exchangePrice?->price;

        $volume = SpotTrade::query()
            ->where('market_id', $market->id)
            ->where('created_at', '>=', now()->subHours(24))
            ->sum('quantity');

        $latestHistory = MarketHistory::query()
            ->where('market_id', $market->id)
            ->latest('timestamp')
            ->first();

        $low = $latestHistory?->low;
        $high = $latestHistory?->high;
        $open = $latestHistory?->open;

        $priceChangePercentage = 0.0;
        if (! is_null($last) && ! is_null($open) && (float) $open != 0.0) {
            $priceChangePercentage = round((($last - $open) / $open) * 100, 2);
        }

        return [
            'low' => $low ?? $last,
            'high' => $high ?? $last,
            'volume' => $volume,
            'last' => $last,
            'open' => $open ?? $last,
            'price_change_percentage' => $priceChangePercentage,
        ];
    }
}

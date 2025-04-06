<?php

namespace App\Services\Exchanges\MarketState;

use App\Models\SpotTrade;
use App\Repositories\Interfaces\MarketRepositoryInterface;
use Illuminate\Support\Facades\Http;
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
        $response = Http::get('https://api.coinex.com/v2/spot/ticker', [
            'market' => $market->market_name,
        ])->json();

        $data = $response['data'][0];

        $volume = SpotTrade::query()
            ->where('market_id', $market->id)
            ->where('created_at', '>=', now()->subHours(24))
            ->sum('quantity');

        return ['low' => $data['low'],
            'high' => $data['high'],
            'volume' => $volume,
            'last' => $data['last'],
            'open' => $data['open'],
            'price_change_percentage' => round((($data['last'] - $data['open']) / $data['open']) * 100, 2),
        ];
    }
}

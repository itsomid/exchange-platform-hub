<?php

namespace App\Services\Exchanges\MarketHistory;

use Illuminate\Support\Facades\Http;

class CoinExMarketHistoryFetcher implements MarketHistoryFetcher
{
    public function fetchHistory(string $market, string $period, int $limit): array
    {
        $response = Http::get('https://api.coinex.com/v2/spot/kline', [
            'market' => $market,
            'period' => $period,
            'limit' => $limit,
        ]);
        $responseData = $response->json();

        if ($response->failed() || $responseData['code'] !== 0) {
            throw new \Exception('Failed to fetch data from CoinEX');
        }

        return array_map(fn(array $item) => [
                'timestamp' => $item['created_at'] / 1000,
                'open' => $item['open'],
                'high' => $item['high'],
                'low' => $item['low'],
                'close' => $item['close'],
                'volume' => $item['volume'],
        ], $responseData['data']);
    }
}

<?php

namespace App\Services\Exchanges\ExchangeData;

use App\Services\Exchanges\Asset\Binance\BinanceRequest;
use Exception;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class BinanceExchangeData implements ExchangeDataInterface
{
    private const NETWORK_ALIASES = [
        'ETH' => 'ERC20',
        'TRX' => 'TRC20',
        'MATIC' => 'POLYGON',
        'AVAX' => 'AVALANCHE',
        'BEP20' => 'BSC',
        'ARB' => 'ARBITRUM',
        'OP' => 'OPTIMISM',
    ];

    public function fetchHistory(string $market, string $period, int $limit): array
    {
        $response = Http::get(config('exchanges.binance.base_url') . '/api/v3/klines', [
            'symbol' => $market,
            'interval' => $period,
            'limit' => $limit,
        ]);

        if ($response->failed()) {
            throw $this->failure($response, "klines for {$market}");
        }

        $data = $response->json();

        if (!is_array($data) || empty($data)) {
            throw new Exception("Binance returned no klines for {$market}");
        }

        // Binance kline: [openTime, open, high, low, close, volume, ...]
        return array_map(fn(array $item) => [
            'timestamp' => $item[0] / 1000,
            'open' => $item[1],
            'high' => $item[2],
            'low' => $item[3],
            'close' => $item[4],
            'volume' => $item[5],
        ], $data);
    }

    public function fetchWithdrawalFee(string $currency): array
    {
        $response = BinanceRequest::sendRequest('GET', '/sapi/v1/capital/config/getall', [
            'coin' => $currency,
        ]);

        if ($response->failed()) {
            throw $this->failure($response, "withdrawal config for {$currency}");
        }

        $data = $response->json();

        if (!is_array($data) || empty($data)) {
            throw new Exception("No chain data available for currency {$currency}.");
        }

        $coin = collect($data)->firstWhere('coin', strtoupper($currency)) ?? $data[0] ?? null;

        if (empty($coin['networkList'])) {
            throw new Exception("No chain data available for currency {$currency}.");
        }

        $networks = array_map(function (array $network) {
            return [
                'network' => $this->normalizeNetwork($network['network']),
                'withdrawal_fee' => $network['withdrawFee'],
                'deposit_enabled' => (bool) $network['depositEnable'],
                'withdraw_enabled' => (bool) $network['withdrawEnable'],
                'min_deposit_amount' => $network['depositDust'] ?? '0',
                'min_withdraw_amount' => $network['withdrawMin'] ?? '0',
                'safe_confirmations' => $network['minConfirm'] ?? 0,
            ];
        }, $coin['networkList']);

        return [
            'currency' => $currency,
            'networks' => $networks,
        ];
    }

    public function fetchMinTrade(): array
    {
        $response = Http::get(config('exchanges.binance.base_url') . '/api/v3/exchangeInfo');

        if (!$response->successful()) {
            throw $this->failure($response, 'exchangeInfo');
        }

        $symbols = $response->json('symbols') ?? [];

        $result = [];
        foreach ($symbols as $symbol) {
            if (($symbol['status'] ?? '') !== 'TRADING') {
                continue;
            }

            $minQty = null;
            foreach ($symbol['filters'] ?? [] as $filter) {
                if (($filter['filterType'] ?? '') === 'LOT_SIZE') {
                    $minQty = $filter['minQty'];
                    break;
                }
            }

            if ($minQty === null) {
                continue;
            }

            $result[] = [
                'market' => $symbol['symbol'],
                'min_amount' => $minQty,
                'base_ccy' => $symbol['baseAsset'],
                'quote_ccy' => $symbol['quoteAsset'],
            ];
        }

        return $result;
    }

    private function failure(Response $response, string $context): Exception
    {
        $code = $response->json('code');
        $message = $response->json('msg') ?? "HTTP {$response->status()} {$response->reason()}";

        return new Exception("Binance {$context}: {$message}" . ($code !== null ? " (code {$code})" : ''));
    }

    private function normalizeNetwork(string $network): string
    {
        return self::NETWORK_ALIASES[$network] ?? $network;
    }
}

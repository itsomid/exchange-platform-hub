<?php

namespace App\Services\Exchanges\ExchangeData;

use App\Services\Exchanges\Asset\Binance\BinanceOrderFormatter;
use App\Services\Exchanges\Asset\Binance\BinanceRequest;
use Exception;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class BinanceExchangeData implements ExchangeDataInterface
{
    private const MIN_NOTIONAL_MARGIN = '1.1';

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

        $pricesResponse = Http::get(config('exchanges.binance.base_url') . '/api/v3/ticker/price');
        if (!$pricesResponse->successful()) {
            throw $this->failure($pricesResponse, 'ticker prices');
        }
        $prices = collect($pricesResponse->json() ?? [])->pluck('price', 'symbol');

        $result = [];
        foreach ($symbols as $symbol) {
            if (($symbol['status'] ?? '') !== 'TRADING') {
                continue;
            }

            $minQty = null;
            $stepSize = null;
            $minNotional = null;
            foreach ($symbol['filters'] ?? [] as $filter) {
                switch ($filter['filterType'] ?? '') {
                    case 'LOT_SIZE':
                        $minQty = $filter['minQty'];
                        $stepSize = $filter['stepSize'] ?? null;
                        break;
                    case 'NOTIONAL':
                    case 'MIN_NOTIONAL':
                        $minNotional = $filter['minNotional'] ?? null;
                        break;
                }
            }

            if ($minQty === null) {
                continue;
            }

            $price = $prices[$symbol['symbol']] ?? null;
            $minAmount = $this->minAmount($minQty, $stepSize, $minNotional, $price);

            $result[] = [
                'market' => $symbol['symbol'],
                'min_amount' => $minAmount,
                'min_qty' => $minQty,
                'min_notional' => $minNotional,
                'min_notional_margin_percent' => bcmul(bcsub(self::MIN_NOTIONAL_MARGIN, '1', 4), '100', 0),
                'price' => $price,
                'limited_by' => $minAmount === $minQty ? 'min_qty' : 'min_notional',
                'base_ccy' => $symbol['baseAsset'],
                'quote_ccy' => $symbol['quoteAsset'],
            ];
        }

        return $result;
    }

    /**
     * Binance rejects an order below LOT_SIZE.minQty and also below NOTIONAL.minNotional
     * (quantity × price), so the minimum is whichever needs more coins. The notional side
     * gets a margin because the price can drop before the next sync.
     */
    private function minAmount(string $minQty, ?string $stepSize, ?string $minNotional, ?string $price): string
    {
        $minNotional = BinanceOrderFormatter::plainDecimal((string) $minNotional);
        $price = BinanceOrderFormatter::plainDecimal((string) $price);

        if (bccomp($minNotional, '0', 18) !== 1 || bccomp($price, '0', 18) !== 1) {
            return $minQty;
        }

        $notionalQty = bcdiv(bcmul($minNotional, self::MIN_NOTIONAL_MARGIN, 18), $price, 18);

        $stepSize = BinanceOrderFormatter::plainDecimal((string) $stepSize);
        if (bccomp($stepSize, '0', 18) === 1) {
            $steps = bcdiv($notionalQty, $stepSize, 0);
            if (bccomp(bcmul($steps, $stepSize, 18), $notionalQty, 18) === -1) {
                $steps = bcadd($steps, '1', 0);
            }
            $notionalQty = bcmul($steps, $stepSize, 18);
        }

        return bccomp($notionalQty, BinanceOrderFormatter::plainDecimal($minQty), 18) === 1
            ? BinanceOrderFormatter::plainDecimal($notionalQty)
            : $minQty;
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

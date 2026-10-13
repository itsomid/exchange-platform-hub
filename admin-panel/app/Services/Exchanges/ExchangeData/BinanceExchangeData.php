<?php

namespace App\Services\Exchanges\ExchangeData;

use App\Services\Exchanges\Asset\Binance\BinanceOrderFormatter;
use App\Services\Exchanges\Asset\Binance\BinanceRequest;
use Exception;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Client\PendingRequest;
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

    public function fetchMinTrade(?array $symbols = null): array
    {
        if ($symbols === null) {
            return $this->fetchMinTradeBatch(null);
        }

        $symbols = array_values(array_unique(array_filter(array_map(
            fn ($symbol) => strtoupper(trim((string) $symbol)),
            $symbols
        ))));

        if ($symbols === []) {
            return [];
        }

        return $this->fetchMinTradeFor($symbols);
    }

    /**
     * The unfiltered exchangeInfo payload is large enough to exhaust the web
     * memory limit once Laravel decodes it. Callers that already know the pairs
     * must pass them so only those rows are downloaded.
     *
     * @param  list<string>  $symbols
     */
    private function fetchMinTradeFor(array $symbols): array
    {
        if (count($symbols) > 40) {
            $rows = [];
            foreach (array_chunk($symbols, 40) as $chunk) {
                $rows = array_merge($rows, $this->fetchMinTradeFor($chunk));
            }

            return $rows;
        }

        try {
            return $this->fetchMinTradeBatch($symbols);
        } catch (InvalidExchangeSymbol) {
            if (count($symbols) === 1) {
                return [];
            }

            $mid = intdiv(count($symbols), 2);

            return array_merge(
                $this->fetchMinTradeFor(array_slice($symbols, 0, $mid)),
                $this->fetchMinTradeFor(array_slice($symbols, $mid)),
            );
        }
    }

    /**
     * @param  list<string>|null  $symbols
     */
    private function fetchMinTradeBatch(?array $symbols): array
    {
        $query = [];
        if ($symbols !== null) {
            $query['symbols'] = json_encode(array_values($symbols));
        }

        $response = $this->binance()->get('/api/v3/exchangeInfo', $query);

        if (!$response->successful()) {
            throw $this->failure($response, 'exchangeInfo');
        }

        $symbolRows = $response->json('symbols') ?? [];
        unset($response);

        $pricesResponse = $this->binance()->get('/api/v3/ticker/price', $query);
        if (!$pricesResponse->successful()) {
            throw $this->failure($pricesResponse, 'ticker prices');
        }

        $prices = [];
        foreach ($pricesResponse->json() ?? [] as $row) {
            if (isset($row['symbol'])) {
                $prices[$row['symbol']] = $row['price'];
            }
        }
        unset($pricesResponse);

        $result = [];
        foreach ($symbolRows as $symbol) {
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

    private function binance(): PendingRequest
    {
        return Http::timeout(8)
            ->connectTimeout(3)
            ->acceptJson()
            ->baseUrl(rtrim((string) config('exchanges.binance.base_url'), '/'));
    }

    private function failure(Response $response, string $context): Exception
    {
        $code = $response->json('code');
        $message = $response->json('msg') ?? "HTTP {$response->status()} {$response->reason()}";

        if ((int) $code === -1121) {
            return new InvalidExchangeSymbol("Binance {$context}: {$message}");
        }

        return new Exception("Binance {$context}: {$message}" . ($code !== null ? " (code {$code})" : ''));
    }

    private function normalizeNetwork(string $network): string
    {
        return self::NETWORK_ALIASES[$network] ?? $network;
    }
}

class InvalidExchangeSymbol extends Exception
{
}

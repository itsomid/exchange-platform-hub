<?php

namespace App\Services\Socket;

use App\Events\MarketUpdated;
use App\Models\Exchange;
use App\Models\ExchangePrice;
use App\Models\Market;
use Exception;
use Illuminate\Support\Facades\Redis;
use Ratchet\Client\WebSocket;
use Ratchet\RFC6455\Messaging\MessageInterface;
use Throwable;

class MexcSocketService
{
    private string $socketUrl = 'wss://wbs.mexc.com/ws';

    private array $coinsPrice = [];

    private ?int $mexcID = null;

    private array $marketIds = [];

    private array $currentMarkets = [];
    private array $exchangePrices = [];
    private int $messageCount = 0;
    private string $buffer = '';

    public function startListener(): void
    {
        gc_enable();
        if (is_null($this->mexcID)) {
            $mexcExchange = Exchange::query()
                ->where('slug', 'mexc')
                ->where('is_active', true)
                ->first();

            if (!$mexcExchange) {
                echo "Error: Mexc exchange not Active.\n";
                return;
            }
            $this->mexcID = $mexcExchange->id;
        }

        $loop = \React\EventLoop\Loop::get();
        $connector = new \Ratchet\Client\Connector($loop);

        $connector($this->socketUrl)
            ->then(
                function (WebSocket $conn) use ($loop) {
                    echo "Connected to WebSocket\n";

                    $this->currentMarkets = $this->fetchMarkets();
                    $this->subscribeToMarkets($conn, $this->currentMarkets);

                    $loop->addPeriodicTimer(60, function () use ($conn) {
                        $this->checkForMarketChanges($conn);
                    });

                    $loop->addPeriodicTimer(10, function () use ($conn) {
                        $conn->send(json_encode(['method' => 'PING']));
                    });

                    $conn->on('message', function (MessageInterface $message) {
                        try {
                            $this->buffer .= $message->getPayload();

                            while (($pos = strpos($this->buffer, '}{')) !== false) {
                                $packet = substr($this->buffer, 0, $pos + 1);
                                $this->buffer = substr($this->buffer, $pos + 1);
                                $this->handlePacket($packet);
                            }

                            if (!empty($this->buffer) && $this->isValidJson($this->buffer)) {
                                $this->handlePacket($this->buffer);
                                $this->buffer = '';
                            }
                        } catch (Throwable $e) {
                            echo 'Error processing message: ' . $e->getMessage() . "\n";
                        }
                    });

                    $conn->on('close', function ($code = null, $reason = null) use ($loop, $conn) {
                        $conn->removeAllListeners();
                        gc_collect_cycles();
                        echo "WebSocket closed: {$code} - {$reason}\n";
                        $this->reconnect($loop);
                    });
                },
                function (Exception $e) {
                    echo "Could not connect to WebSocket: {$e->getMessage()}\n";
                    echo "Please check your network connection and firewall settings.\n";
                    echo "Full error: " . $e . "\n";
                }
            );

        $loop->run();
    }

    private function reconnect($loop): void
    {
        echo "Reconnecting to WebSocket...\n";
        $loop->addTimer(5, function () {
            $this->startListener();
        });
    }

    private function handlePacket(string $packet): void
    {
        $decodedData = json_decode($packet, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            echo 'Invalid JSON format: ' . json_last_error_msg() . "\n";
            return;
        }
        $this->processMessage($decodedData);

        if ($this->messageCount++ % 10 == 0) {
            gc_collect_cycles();
            echo "Memory usage: " . round(memory_get_usage(true) / 1024 / 1024, 2) . " MB\n";
        }
    }

    private function isValidJson(string $string): bool
    {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }

    private function processMessage(array $data): void
    {
        if (isset($data['method']) && $data['method'] === 'PONG') {
            echo "Received PONG from server\n";
            return;
        }
        if (isset($data['c']) && str_starts_with($data['c'], 'spot@public.deals.v3.api')) {
            $deals = $data['d']['deals'] ?? [];
            foreach ($deals as $deal) {
                $this->updateCurrencyPrice($data['s'], $deal['p'] ?? null, null, $deal);
            }
        }
    }

    private function decompressMessage(string $data): ?string
    {
        return $data;
    }

    private function updateCurrencyPrice(string $symbol, ?string $lastPrice, ?string $openPrice, array $data): void
    {
        $baseCurrency = str_replace('USDT', '', $symbol);
        $priceKey = md5($lastPrice);
        if (!isset($this->coinsPrice[$baseCurrency]) || $this->coinsPrice[$baseCurrency] !== $priceKey) {
            $this->coinsPrice[$baseCurrency] = $priceKey;

            echo $baseCurrency . ': last->' . $lastPrice . PHP_EOL;

            if (!isset($this->marketIds[$baseCurrency]['id'])) {
                $market = Market::query()
                    ->where('base_currency', $baseCurrency)
                    ->first();

                if (!$market) {
                    echo "Market not found for base_currency: $baseCurrency\n";
                    return;
                }

                $this->marketIds[$baseCurrency] = [
                    'id' => $market->id,
                    'last_update' => time()
                ];
            }
            $marketId = $this->marketIds[$baseCurrency]['id'];

            if (!isset($this->exchangePrices[$marketId]) || time() - $this->exchangePrices[$marketId]['last_update'] > 60) {
                $exchangePriceModel = ExchangePrice::query()
                    ->where('market_id', $marketId)
                    ->where('exchange_id', $this->mexcID)
                    ->first();

                if (!$exchangePriceModel) {
                    echo "ExchangePrice not found for base_currency: $baseCurrency\n";
                    return;
                }

                $this->exchangePrices[$marketId] = [
                    'exchange_profit_sell' => $exchangePriceModel->exchange_profit_sell,
                    'exchange_profit_buy' => $exchangePriceModel->exchange_profit_buy,
                    'last_update' => time()
                ];
            }

            $profitSell = $this->exchangePrices[$marketId]['exchange_profit_sell'];
            $profitBuy = $this->exchangePrices[$marketId]['exchange_profit_buy'];

            ExchangePrice::query()
                ->where('market_id', $marketId)
                ->where('exchange_id', $this->mexcID)
                ->update([
                    'price' => $lastPrice,
                ]);

            $sellPrice = bcmul($lastPrice, ($profitSell / 100) + 1, 8);
            $buyPrice = bcmul($lastPrice, ($profitBuy / 100) + 1, 8);

            Redis::publish('market_prices', json_encode([
                'base_currency' => $baseCurrency,
                'sell_price' => $sellPrice,
                'buy_price' => $buyPrice,
                'last_price' => $lastPrice,
                'timestamp' => now()->timestamp,
            ]));

            MarketUpdated::dispatch($marketId, [
                'last' => $lastPrice,
            ]);
        }
    }

    private function fetchMarkets(): array
    {
        return Market::query()
            ->where('is_active', true)
            ->whereHas('activeExchangePrice', function ($query) {
                $query->where('exchange_id', $this->mexcID);
            })->pluck('base_currency')->map(fn($market) => $market . 'USDT')
            ->toArray();
    }

    private function subscribeToMarkets(WebSocket $stream, array $markets): void
    {
        if (empty($markets)) {
            echo "No markets to subscribe.\n";
            return;
        }

        $params = array_map(function ($market) {
            return "spot@public.deals.v3.api@{$market}";
        }, $markets);

        $subscribeMessage = [
            'method' => 'SUBSCRIPTION',
            'params' => $params,
        ];
        $stream->send(json_encode($subscribeMessage));
        echo 'Subscribed to markets: ' . implode(', ', $markets) . "\n";
    }

    private function checkForMarketChanges($stream): void
    {
        $newMarkets = $this->fetchMarkets();

        if ($newMarkets !== $this->currentMarkets) {
            echo "Market list updated. Resubscribing...\n";

            $oldMarkets = $this->currentMarkets;
            $this->currentMarkets = $newMarkets;

            $marketsToUnsubscribe = array_diff($oldMarkets, $newMarkets);
            if (!empty($marketsToUnsubscribe)) {
                $unsubscribeParams = array_map(function ($market) {
                    return "spot@public.deals.v3.api@{$market}";
                }, $marketsToUnsubscribe);

                $unsubscribeMessage = [
                    'method' => 'UNSUBSCRIPTION',
                    'params' => $unsubscribeParams,
                ];
                $stream->send(json_encode($unsubscribeMessage));
                echo 'Unsubscribed from markets: ' . implode(', ', $marketsToUnsubscribe) . "\n";
            }

            $marketsToSubscribe = array_diff($newMarkets, $oldMarkets);
            if (!empty($marketsToSubscribe)) {
                $this->subscribeToMarkets($stream, $marketsToSubscribe);
            }
        }
    }
}

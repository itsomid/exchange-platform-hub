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

class CoinExSocketService
{
    private string $socketUrl = 'wss://socket.coinex.com/v2/spot';

    private array $coinsPrice = [];

    private ?int $coinexID = null;

    private array $marketIds = [];

    private array $currentMarkets = [];
    private array $exchangePrices = [];

    public function startListener(): void
    {
        if (is_null($this->coinexID)) {
            $coinExExchange = Exchange::query()
                ->where('slug', 'coinex')
                ->where('is_active', true)
                ->first();
            $this->coinexID = $coinExExchange->id;
        }

        $reactConnector = new \React\Socket\Connector([
            'dns' => '1.1.1.1',
            'timeout' => 10,
        ]);
        $loop = \React\EventLoop\Loop::get();
        $connector = new \Ratchet\Client\Connector($loop, $reactConnector);

        $connector($this->socketUrl)
            ->then(
                function (WebSocket $conn) use ($loop) {
                    echo "Connected to WebSocket\n";

                    // Fetch initial markets and send subscription
                    $this->currentMarkets = $this->fetchMarkets();
                    $this->subscribeToMarkets($conn, $this->currentMarkets);

                    // Monitor database changes in a periodic timer
                    $loop->addPeriodicTimer(60, function () use ($conn) {
                        $this->checkForMarketChanges($conn);
                    });
                    // Handle incoming messages
                    $conn->on('message', function (MessageInterface $message) {
                        try {
                            $data = $this->decompressMessage($message);

                            if ($data === null) {
                                echo "Invalid or corrupted message received\n";

                                return;
                            }
                            // Decode JSON data
                            $decodedData = json_decode($data, true);
                            if (json_last_error() !== JSON_ERROR_NONE) {
                                echo 'Invalid JSON format: ' . json_last_error_msg() . "\n";

                                return;
                            }

                            $this->processMessage($decodedData);
                        } catch (Throwable $e) {
                            echo 'Error processing message: ' . $e->getMessage() . "\n";
                        }
                    });

                    // Handle connection close
                    $conn->on('close', function ($code = null, $reason = null) use ($loop) {
                        echo "WebSocket closed: {$code} - {$reason}\n";
                        $this->reconnect($loop);
                    });
                },
                function (Exception $e) {
                    echo "Could not connect to WebSocket: {$e->getMessage()}\n";
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

    private function processMessage(array $data): void
    {
        // Check for the "state.update" method
        if (isset($data['method']) && $data['method'] === 'state.update') {
            $markets = $data['data']['state_list'] ?? [];
            foreach ($markets as $market) {
                $this->updateCurrencyPrice($market['market'], $market['last'] ?? null, $market['open'] ?? null, $market);
            }
        }
    }

    private function decompressMessage(string $data): ?string
    {
        // Attempt decompression methods
        $message = zlib_decode($data);
        if ($message === false) {
            echo "Failed to decompress message.\n";

            return null;
        }

        return $message;
    }

    private function updateCurrencyPrice(string $symbol, ?string $lastPrice, ?string $openPrice, array $data): void
    {
        $baseCurrency = str_replace('USDT', '', $symbol);

        if (
            !isset($this->coinsPrice[$baseCurrency]) ||
            $this->coinsPrice[$baseCurrency]['last'] !== $lastPrice ||
            $this->coinsPrice[$baseCurrency]['open'] !== $openPrice
        ) {
            echo $baseCurrency . ': last->' . $lastPrice . PHP_EOL;
            echo $baseCurrency . ': open->' . $openPrice . PHP_EOL;

            // Update cached prices
            $this->coinsPrice[$baseCurrency] = [
                'last' => $lastPrice,
                'open' => $openPrice,
            ];
            if (!isset($this->marketIds[$baseCurrency])) {
                $market = Market::query()
                    ->where('base_currency', $baseCurrency)
                    ->first();
                $this->marketIds[$baseCurrency] = $market->id;
            }

            // Find the exchange price related to the market for CoinEx
            if (!isset($this->exchangePrices[$this->marketIds[$baseCurrency]]) || time() - $this->exchangePrices[$this->marketIds[$baseCurrency]]['last_update'] > 60) {
                $exchangePriceModel = ExchangePrice::query()
                    ->where('market_id', $this->marketIds[$baseCurrency])
                    ->where('exchange_id', $this->coinexID)
                    ->first();
                $this->exchangePrices[$this->marketIds[$baseCurrency]] = [
                    'exchange_profit_sell' => $exchangePriceModel->exchange_profit_sell,
                    'exchange_profit_buy' => $exchangePriceModel->exchange_profit_buy,
                    'last_update' => time()
                ];
            }

            ExchangePrice::query()
                ->where('market_id', $this->marketIds[$baseCurrency])
                ->where('exchange_id', $this->coinexID)
                ->update([
                    'price' => $lastPrice,
                    'open_price' => $openPrice,
                ]);

            $sellPrice = bcmul($lastPrice, ($this->exchangePrices[$this->marketIds[$baseCurrency]]['exchange_profit_sell'] / 100) + 1, 8);
            $buyPrice = bcmul($lastPrice, ($this->exchangePrices[$this->marketIds[$baseCurrency]]['exchange_profit_buy'] / 100) + 1, 8);
            
            $sellOpenPrice = bcmul($openPrice, ($this->exchangePrices[$this->marketIds[$baseCurrency]]['exchange_profit_sell'] / 100) + 1, 8);
            $buyOpenPrice = bcmul($openPrice, ($this->exchangePrices[$this->marketIds[$baseCurrency]]['exchange_profit_buy'] / 100) + 1, 8);

            // Publish to Redis
            Redis::publish('market_prices', json_encode([
                'base_currency' => $baseCurrency,
                'sell_price' => $sellPrice,
                'sell_open_price' => $sellOpenPrice,
                'buy_price' => $buyPrice,
                'buy_open_price' => $buyOpenPrice,
                'price_change_percentage' => round((($lastPrice - $openPrice) / $openPrice) * 100, 2),
                'timestamp' => now()->timestamp,
            ]));

            MarketUpdated::dispatch($this->marketIds[$baseCurrency], [
                'low' => $data['low'],
                'high' => $data['high'],
                'last' => $data['last'],
                'open' => $data['open'],
                'price_change_percentage' => round((($data['last'] - $data['open']) / $data['open']) * 100, 2),
            ]);
        }
    }

    private function fetchMarkets(): array
    {
        // Fetch current markets from the database
        return Market::query()
            ->whereHas('activeExchangePrice', function ($query) {
                $query->where('exchange_id', $this->coinexID);
            })->pluck('base_currency')->map(fn($market) => $market . 'USDT')
            ->toArray();
    }

    private function subscribeToMarkets(WebSocket $stream, array $markets): void
    {
        if (empty($markets)) {
            echo "No markets to subscribe.\n";

            return;
        }

        $subscribeMessage = [
            'method' => 'state.subscribe',
            'params' => ['market_list' => $markets],
            'id' => 1,
        ];
        $stream->send(json_encode($subscribeMessage));
        echo 'Subscribed to markets: ' . implode(', ', $markets) . "\n";
    }

    private function checkForMarketChanges($stream): void
    {
        $newMarkets = $this->fetchMarkets();

        // Compare current markets with the new list
        if ($newMarkets !== $this->currentMarkets) {
            echo "Market list updated. Resubscribing...\n";
            $this->currentMarkets = $newMarkets;

            // Send a new subscription with the updated list
            $this->subscribeToMarkets($stream, $newMarkets);
        }
    }
}

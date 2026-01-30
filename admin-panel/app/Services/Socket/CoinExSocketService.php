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
    private int $messageCount = 0;

    public function startListener(): void
    {
        gc_enable();
        if (is_null($this->coinexID)) {
            $coinExExchange = Exchange::query()
                ->where('slug', 'coinex')
                ->first();

            $this->coinexID = $coinExExchange->id;
        }

        $reactConnector = new \React\Socket\Connector([
            'dns' => '8.8.8.8',
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
                            if ($this->messageCount++ % 10 == 0) {
                                gc_collect_cycles();
                                echo "Memory usageeeeeeeeeeeeeee: " . round(memory_get_usage(true) / 1024 / 1024, 2) . " MB\n";
                            }
                        } catch (Throwable $e) {
                            echo 'Error processing message: ' . $e->getMessage() . "\n";
                        }
                    });

                    // Handle connection close
                    $conn->on('close', function ($code = null, $reason = null) use ($loop, $conn) {
                        $conn->removeAllListeners();
                        gc_collect_cycles();
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
        $priceKey = md5(($lastPrice ?? '') . '|' . ($openPrice ?? ''));
        if (isset($this->coinsPrice[$baseCurrency]) && $this->coinsPrice[$baseCurrency] === $priceKey) {
            return;
        }
        $this->coinsPrice[$baseCurrency] = $priceKey;

        echo $baseCurrency . ': last->' . $lastPrice . PHP_EOL;

        $marketId = $this->getMarketIdForBaseCurrency($baseCurrency);
        if ($marketId === null) {
            return;
        }

        $profits = $this->getExchangeProfitForMarket($marketId, $baseCurrency);
        if ($profits === null) {
            return;
        }

        $this->updateExchangePriceForMarket($marketId, $lastPrice, $openPrice);

        $prices = $this->calculatePrices($lastPrice, $openPrice, $profits['sell'], $profits['buy']);

        $priceChangePercentage = $this->calculateChangePercentage($lastPrice, $openPrice);

        Redis::publish('market_prices', json_encode([
            'base_currency' => $baseCurrency,
            'sell_price' => $prices['sell_price'],
            'sell_open_price' => $prices['sell_open_price'],
            'buy_price' => $prices['buy_price'],
            'buy_open_price' => $prices['buy_open_price'],
            'last_price' => $lastPrice,
            'price_change_percentage' => $priceChangePercentage,
            'timestamp' => now()->timestamp,
        ]));

        MarketUpdated::dispatch($marketId, [
            'low' => $data['low'] ?? null,
            'high' => $data['high'] ?? null,
            'last' => $lastPrice,
            'open' => $openPrice,
            'price_change_percentage' => $priceChangePercentage,
        ]);
    }

    private function getMarketIdForBaseCurrency(string $baseCurrency): ?int
    {
        if (isset($this->marketIds[$baseCurrency]['id'])) {
            return $this->marketIds[$baseCurrency]['id'];
        }

        $market = Market::query()
            ->where('base_currency', $baseCurrency)
            ->first();

        if (!$market) {
            echo "Market not found for base_currency: $baseCurrency\n";
            return null;
        }

        $this->marketIds[$baseCurrency] = [
            'id' => $market->id,
            'last_update' => time()
        ];

        return $market->id;
    }

    private function getExchangeProfitForMarket(int $marketId, string $baseCurrency): ?array
    {
        if (isset($this->exchangePrices[$marketId]) && time() - $this->exchangePrices[$marketId]['last_update'] <= 60) {
            return [
                'sell' => $this->exchangePrices[$marketId]['exchange_profit_sell'],
                'buy' => $this->exchangePrices[$marketId]['exchange_profit_buy'],
            ];
        }

        $exchangePriceModel = ExchangePrice::query()
            ->where('market_id', $marketId)
            ->where('exchange_id', $this->coinexID)
            ->first();

        if (!$exchangePriceModel) {
            echo "ExchangePrice not found for base_currency: $baseCurrency\n";
            return null;
        }

        $this->exchangePrices[$marketId] = [
            'exchange_profit_sell' => $exchangePriceModel->exchange_profit_sell,
            'exchange_profit_buy' => $exchangePriceModel->exchange_profit_buy,
            'last_update' => time()
        ];

        return [
            'sell' => $exchangePriceModel->exchange_profit_sell,
            'buy' => $exchangePriceModel->exchange_profit_buy,
        ];
    }

    private function updateExchangePriceForMarket(int $marketId, ?string $lastPrice, ?string $openPrice): void
    {
        ExchangePrice::query()
            ->where('market_id', $marketId)
            ->where('exchange_id', $this->coinexID)
            ->update([
                'price' => $lastPrice,
                'open_price' => $openPrice,
            ]);
    }

    private function calculatePrices(?string $lastPrice, ?string $openPrice, float $profitSell, float $profitBuy): array
    {
        $sellPrice = bcmul($lastPrice ?? '0', ($profitSell / 100) + 1, 8);
        $buyPrice = bcmul($lastPrice ?? '0', ($profitBuy / 100) + 1, 8);

        $sellOpenPrice = bcmul($openPrice ?? '0', ($profitSell / 100) + 1, 8);
        $buyOpenPrice = bcmul($openPrice ?? '0', ($profitBuy / 100) + 1, 8);

        return [
            'sell_price' => $sellPrice,
            'buy_price' => $buyPrice,
            'sell_open_price' => $sellOpenPrice,
            'buy_open_price' => $buyOpenPrice,
        ];
    }

    private function calculateChangePercentage(?string $lastPrice, ?string $openPrice): float
    {
        if ($openPrice === null || $openPrice == 0 || $lastPrice === null) {
            return 0.0;
        }

        $last = (float) $lastPrice;
        $open = (float) $openPrice;

        return round((($last - $open) / $open) * 100, 2);
    }

    private function fetchMarkets(): array
    {
        // Fetch current markets from the database
        return Market::query()
            ->where('price_update_enabled', true)
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

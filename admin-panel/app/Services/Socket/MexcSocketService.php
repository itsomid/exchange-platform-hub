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
    private string $socketUrl = 'wss://wbs-api.mexc.com/ws';

    private array $coinsPrice = [];

    private ?int $mexcID = null;

    private array $marketIds = [];

    private array $currentMarkets = [];
    private array $exchangePrices = [];
    private int $messageCount = 0;
    private string $buffer = '';
    private int $connectionStartTime = 0;
    private array $activeTimers = []; // Store active timer references
    private const MAX_CONNECTION_HOURS = 23; // Reconnect every 23 hours for production stability

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

        // Create the initial connection
        $this->createConnection($loop);

        $loop->run();
    }

    private function reconnect($loop): void
    {
        static $reconnecting = false;

        if ($reconnecting) {
            return; // Prevent multiple simultaneous reconnection attempts
        }

        $reconnecting = true;
        echo "Reconnecting to WebSocket...\n";

        // Reset connection tracking
        $this->connectionStartTime = 0;

        // Clear any existing state
        $this->coinsPrice = [];
        $this->marketIds = [];
        $this->exchangePrices = [];
        $this->messageCount = 0;
        $this->buffer = '';

        // Wait 5 seconds before reconnecting to avoid rapid reconnection loops
        $loop->addTimer(5, function () use ($loop) {
            echo "Attempting to reconnect at: " . date('Y-m-d H:i:s') . "\n";

            // Reset the reconnecting flag
            static $reconnecting;
            $reconnecting = false;

            // Create new connection within the existing loop
            $this->createConnection($loop);
        });
    }

    private function createConnection($loop): void
    {
        $connector = new \Ratchet\Client\Connector($loop);

        $connector($this->socketUrl)
            ->then(
                function (WebSocket $conn) use ($loop) {
                    echo "Connected to WebSocket\n";
                    $this->connectionStartTime = time();
                    echo "Connection established at: " . date('Y-m-d H:i:s', $this->connectionStartTime) . "\n";

                    $this->currentMarkets = $this->fetchMarkets();
                    $this->subscribeToMarkets($conn, $this->currentMarkets);

                    // Clear any existing timers
                    foreach ($this->activeTimers as $timer) {
                        $loop->cancelTimer($timer);
                    }
                    $this->activeTimers = [];

                    // Store timer references to cancel them later
                    $this->activeTimers[] = $loop->addPeriodicTimer(60, function () use ($conn) {
                        $this->checkForMarketChanges($conn);
                    });

                    $this->activeTimers[] = $loop->addPeriodicTimer(10, function () use ($conn) {
                        $conn->send(json_encode(['method' => 'PING']));
                    });

                    // Check connection age every 30 minutes and reconnect if needed
                    $this->activeTimers[] = $loop->addPeriodicTimer(1800, function () use ($conn, $loop) {
                        if ($this->connectionStartTime > 0) {
                            $connectionAge = time() - $this->connectionStartTime;
                            $hoursConnected = round($connectionAge / 3600, 1);
                            echo "Connection age: " . $hoursConnected . " hours (" . $connectionAge . " seconds)\n";

                            if ($connectionAge >= (self::MAX_CONNECTION_HOURS * 3600)) {
                                echo "Approaching connection limit. Proactively reconnecting...\n";
                                $conn->close(1000, 'Proactive reconnection for stability');
                            }
                        }
                    });

                    $conn->on('message', function (MessageInterface $message) {
                        try {
                            $payload = $message->getPayload();

                            // Check if it's JSON or binary data
                            if ($this->isValidJson($payload)) {
                                // Handle JSON messages (subscription responses, PONG, etc.)
                                $this->handlePacket($payload);
                            } else {
                                // Handle binary Protobuf data
                                $this->handleProtobufData($payload);
                            }
                        } catch (Throwable $e) {
                            echo 'Error processing message: ' . $e->getMessage() . "\n";
                        }
                    });

                    $conn->on('close', function ($code = null, $reason = null) use ($loop, $conn) {
                        $conn->removeAllListeners();

                        // Cancel all active timers to prevent multiple timers running
                        foreach ($this->activeTimers as $timer) {
                            $loop->cancelTimer($timer);
                        }
                        $this->activeTimers = [];

                        gc_collect_cycles();

                        $connectionDuration = $this->connectionStartTime > 0 ? time() - $this->connectionStartTime : 0;
                        $secondsConnected = $connectionDuration;

                        echo "Connection was active for: " . $secondsConnected . " seconds\n";

                        // Enhanced error handling for different close codes
                        switch ($code) {
                            case 1000:
                                if (strpos($reason, 'Proactive reconnection') !== false) {
                                    echo "WebSocket closed proactively before limit: {$reason}\n";
                                } else {
                                    echo "WebSocket closed normally: {$reason}\n";
                                }
                                break;
                            case 1006:
                                echo "WebSocket closed abnormally (connection lost): {$reason}\n";
                                break;
                            case 1011:
                                echo "WebSocket closed due to server error: {$reason}\n";
                                break;
                            default:
                                echo "WebSocket closed: {$code} - {$reason}\n";
                        }

                        // Log the closure for debugging
                        error_log("MEXC WebSocket closed with code {$code}: {$reason}. Duration: " . $secondsConnected . " seconds");

                        $this->reconnect($loop);
                    });
                },
                function (Exception $e) {
                    echo "Could not connect to WebSocket: {$e->getMessage()}\n";
                    echo "Please check your network connection and firewall settings.\n";

                    // Enhanced error logging and handling
                    error_log("MEXC WebSocket connection failed: " . $e->getMessage());

                    // Check for specific connection issues
                    if (strpos($e->getMessage(), 'timeout') !== false) {
                        echo "Connection timeout detected. Retrying in 10 seconds...\n";
                        $loop->addTimer(10, function () use ($loop) {
                            $this->createConnection($loop);
                        });
                    } elseif (strpos($e->getMessage(), 'refused') !== false) {
                        echo "Connection refused. Server may be down. Retrying in 30 seconds...\n";
                        $loop->addTimer(30, function () use ($loop) {
                            $this->createConnection($loop);
                        });
                    } else {
                        // For other errors, try reconnecting after 5 seconds
                        $loop->addTimer(5, function () use ($loop) {
                            $this->createConnection($loop);
                        });
                    }

                    echo "Full error: " . $e . "\n";
                }
            );
    }

    private function handlePacket(string $packet): void
    {
        // First try to decode as JSON (for subscription responses, PONG, etc.)
        $decodedData = json_decode($packet, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            // Successfully decoded as JSON
            $this->processMessage($decodedData);
        } else {
            // Handle binary Protobuf data
            $this->handleProtobufData($packet);
        }

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

    private function handleProtobufData(string $binaryData): void
    {
        // Simple binary data parser for MEXC price data
        // This is a basic implementation without full Protobuf support

        try {
            // Try to extract readable strings that might contain price info
            $readableData = '';
            for ($i = 0; $i < strlen($binaryData); $i++) {
                $char = $binaryData[$i];
                if (ctype_print($char) || $char === '.' || ctype_digit($char)) {
                    $readableData .= $char;
                } else {
                    $readableData .= ' ';
                }
            }
            // echo "Readable data: " . $readableData . "\n";

            // Extract symbol from the data (look for pattern like BTCUSDT)
            $symbol = null;
            if (preg_match('/([A-Z]{3,10}USDT)/', $readableData, $symbolMatches)) {
                $symbol = $symbolMatches[1];
                // echo "Found symbol: " . $symbol . "\n";
            }

            // Look for price patterns - find the main price (usually the first decimal number after symbol)
            if (preg_match_all('/\d+\.\d+/', $readableData, $matches)) {
                $prices = $matches[0];
                if (!empty($prices)) {
                    // echo "All extracted prices: " . implode(', ', $prices) . "\n";

                    // The main price is usually the first decimal number that appears after the symbol
                    // For crypto pairs, this is typically the actual trading price
                    $mainPrice = $prices[0]; // Take the first price found

                    // echo "Selected main price: " . $mainPrice . "\n";

                    if ($symbol) {
                        // echo "Updating price for " . $symbol . " to " . $mainPrice . "\n";
                        $this->updateCurrencyPrice($symbol, (string)$mainPrice, null, ['extracted' => true]);
                    } else {
                        echo "Symbol not found, cannot update price\n";
                    }
                }
            }
        } catch (Exception $e) {
            echo "Error parsing binary data: " . $e->getMessage() . "\n";
        }
    }

    private function processMessage(array $data): void
    {
        // Debug: Show what data we're receiving
        // echo "Received data: " . json_encode($data) . "\n";

        if (isset($data['method']) && $data['method'] === 'PONG') {
            echo "Received PONG from server\n";
            return;
        }

        // Handle subscription/unsubscription responses
        if (isset($data['code'])) {
            if ($data['code'] === 0) {
                echo "Subscription successful: {$data['msg']}\n";
            } else {
                echo "Subscription error (code {$data['code']}): {$data['msg']}\n";
                // Log error for debugging
                error_log("MEXC WebSocket subscription error: " . json_encode($data));
            }
            return;
        }

        // Handle new protobuf format with publicdeals structure
        if (isset($data['channel']) && str_starts_with($data['channel'], 'spot@public.aggre.deals.v3.api.pb')) {
            $symbol = $data['symbol'] ?? null;
            $publicDeals = $data['publicdeals'] ?? null;

            if ($symbol && $publicDeals && isset($publicDeals['dealsList'])) {
                foreach ($publicDeals['dealsList'] as $deal) {
                    $this->updateCurrencyPrice($symbol, $deal['price'] ?? null, null, $deal);
                }
            }
        }

        // Fallback for old format (backward compatibility)
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
            ->where('price_update_enabled', true)
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
            return "spot@public.aggre.deals.v3.api.pb@100ms@{$market}";
        }, $markets);

        $subscribeMessage = [
            'method' => 'SUBSCRIPTION',
            'params' => $params,
        ];
        $stream->send(json_encode($subscribeMessage));
        echo 'Subscribed to MEXC markets: ' . implode(', ', $markets) . "\n";
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
                    return "spot@public.aggre.deals.v3.api.pb@100ms@{$market}";
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

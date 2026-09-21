<?php

namespace App\Services\Socket;

use App\Events\MarketPriceUpdated;
use App\Events\MarketUpdated;
use App\Models\Exchange;
use App\Models\ExchangePrice;
use App\Models\Market;
use Exception;
use Illuminate\Support\Facades\Cache;
use Ratchet\Client\WebSocket;
use Ratchet\RFC6455\Messaging\MessageInterface;
use Throwable;

class BinanceSocketService
{
    private string $socketUrl = 'wss://stream.binance.com:9443/ws';

    // Binance drops every connection at the 24 hour mark, so cycle it earlier.
    private const MAX_CONNECTION_HOURS = 23;

    // Binance pings every 20 seconds. Pawl answers those pings itself, so a gap
    // this long means the socket died without ever delivering a close frame.
    private const PING_TIMEOUT_SECONDS = 90;

    // A single connection may listen to at most 1024 streams.
    private const MAX_STREAMS = 1024;

    // Binance allows 5 inbound messages per second and counts its own ping/pong
    // frames towards it, so cap outbound commands well below that.
    private const STREAMS_PER_MESSAGE = 100;
    private const SECONDS_BETWEEN_MESSAGES = 0.5;

    private const BASE_RECONNECT_DELAY = 5;
    private const MAX_RECONNECT_DELAY = 120;

    private ?int $binanceID = null;

    private array $coinsPrice = [];

    private array $marketIds = [];

    private array $currentMarkets = [];
    private array $exchangePrices = [];
    private array $activeTimers = [];
    private int $messageCount = 0;
    private int $requestId = 1;
    private int $connectionStartTime = 0;
    private int $lastPingAt = 0;
    private int $reconnectAttempts = 0;
    private float $nextSendAt = 0;
    private bool $reconnecting = false;

    public function startListener(): void
    {
        gc_enable();
        if (is_null($this->binanceID)) {
            $binanceExchange = Exchange::query()
                ->where('slug', 'binance')
                ->where('is_active', true)
                ->first();

            if (!$binanceExchange) {
                echo "Error: Binance exchange not Active.\n";
                return;
            }
            $this->binanceID = $binanceExchange->id;
        }

        $loop = \React\EventLoop\Loop::get();

        $this->createConnection($loop);

        $loop->run();
    }

    private function createConnection($loop): void
    {
        $reactConnector = new \React\Socket\Connector([
            'dns' => '8.8.8.8',
            'timeout' => 10,
        ]);
        $connector = new \Ratchet\Client\Connector($loop, $reactConnector);

        $connector($this->socketUrl)
            ->then(
                function (WebSocket $conn) use ($loop) {
                    $this->onConnected($loop, $conn);
                },
                function (Exception $e) use ($loop) {
                    echo "Could not connect to Binance WebSocket: {$e->getMessage()}\n";
                    error_log('Binance WebSocket connection failed: ' . $e->getMessage());

                    $this->reconnect($loop);
                }
            );
    }

    private function onConnected($loop, WebSocket $conn): void
    {
        $this->reconnectAttempts = 0;
        $this->connectionStartTime = time();
        $this->lastPingAt = time();

        echo 'Connected to Binance WebSocket at: ' . date('Y-m-d H:i:s', $this->connectionStartTime) . "\n";

        $this->cancelTimers($loop);

        $this->currentMarkets = $this->fetchMarkets();
        $this->sendStreamCommand($loop, $conn, 'SUBSCRIBE', $this->currentMarkets);

        $this->activeTimers[] = $loop->addPeriodicTimer(60, function () use ($loop, $conn) {
            $this->checkForMarketChanges($loop, $conn);
        });

        $this->activeTimers[] = $loop->addPeriodicTimer(30, function () use ($conn) {
            $silentFor = time() - $this->lastPingAt;

            if ($silentFor > self::PING_TIMEOUT_SECONDS) {
                echo "No ping from Binance for {$silentFor} seconds. Dropping the connection.\n";
                $conn->close(1000, 'Ping timeout');
            }
        });

        $this->activeTimers[] = $loop->addPeriodicTimer(600, function () use ($conn) {
            $connectionAge = time() - $this->connectionStartTime;

            echo 'Connection age: ' . round($connectionAge / 3600, 1) . ' hours. Memory usage: '
                . round(memory_get_usage(true) / 1024 / 1024, 2) . " MB\n";

            if ($connectionAge >= self::MAX_CONNECTION_HOURS * 3600) {
                echo "Approaching the 24 hour limit. Proactively reconnecting...\n";
                $conn->close(1000, 'Proactive reconnection before the 24 hour limit');
            }
        });

        // Pawl replies to every ping with a matching pong on its own; we only
        // track them so the watchdog above can tell a live socket from a dead one.
        $conn->on('ping', function () {
            $this->lastPingAt = time();
        });

        $conn->on('message', function (MessageInterface $message) use ($conn) {
            try {
                $decodedData = json_decode($message->getPayload(), true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    echo 'Invalid JSON format: ' . json_last_error_msg() . "\n";
                    return;
                }

                $this->processMessage($decodedData, $conn);

                if ($this->messageCount++ % 100 == 0) {
                    gc_collect_cycles();
                }
            } catch (Throwable $e) {
                echo 'Error processing message: ' . $e->getMessage() . "\n";
            }
        });

        $conn->on('close', function ($code = null, $reason = null) use ($loop, $conn) {
            $conn->removeAllListeners();
            $this->cancelTimers($loop);
            gc_collect_cycles();

            $secondsConnected = $this->connectionStartTime > 0 ? time() - $this->connectionStartTime : 0;

            echo "Binance WebSocket closed: {$code} - {$reason}. Connection was active for {$secondsConnected} seconds\n";
            error_log("Binance WebSocket closed with code {$code}: {$reason}. Duration: {$secondsConnected} seconds");

            $this->reconnect($loop);
        });
    }

    private function reconnect($loop): void
    {
        if ($this->reconnecting) {
            return;
        }
        $this->reconnecting = true;

        $this->connectionStartTime = 0;
        $this->lastPingAt = 0;
        $this->nextSendAt = 0;
        $this->coinsPrice = [];
        $this->marketIds = [];
        $this->exchangePrices = [];
        $this->currentMarkets = [];
        $this->messageCount = 0;

        // Back off between attempts so a sustained outage cannot burn through the
        // limit of 300 connection attempts per 5 minutes per IP.
        $delay = min(self::MAX_RECONNECT_DELAY, self::BASE_RECONNECT_DELAY * (2 ** $this->reconnectAttempts));
        $this->reconnectAttempts++;

        echo "Reconnecting to Binance in {$delay} seconds...\n";

        $loop->addTimer($delay, function () use ($loop) {
            $this->reconnecting = false;
            $this->createConnection($loop);
        });
    }

    private function cancelTimers($loop): void
    {
        foreach ($this->activeTimers as $timer) {
            $loop->cancelTimer($timer);
        }
        $this->activeTimers = [];
    }

    private function processMessage(array $data, WebSocket $conn): void
    {
        // SUBSCRIBE / UNSUBSCRIBE acknowledgements carry a null result.
        if (array_key_exists('result', $data)) {
            if ($data['result'] !== null) {
                echo 'Binance command response: ' . json_encode($data) . "\n";
            }
            return;
        }

        if (isset($data['code'])) {
            echo "Binance error (code {$data['code']}): " . ($data['msg'] ?? '') . "\n";
            error_log('Binance WebSocket error: ' . json_encode($data));
            return;
        }

        $event = $data['e'] ?? null;

        if ($event === 'serverShutdown') {
            echo "Binance announced a server shutdown. Closing to reconnect early.\n";
            $conn->close(1000, 'Server shutdown announced');
            return;
        }

        if ($event === '24hrTicker') {
            $this->updateCurrencyPrice($data['s'], $data['c'] ?? null, $data['o'] ?? null, [
                'low' => $data['l'] ?? null,
                'high' => $data['h'] ?? null,
            ]);
        }
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

        if ($lastPrice !== null) {
            Cache::put("market:price:{$baseCurrency}USDT", $lastPrice, now()->addMinutes(5));
        }

        $prices = $this->calculatePrices($lastPrice, $openPrice, $profits['sell'], $profits['buy']);

        $priceChangePercentage = $this->calculateChangePercentage($lastPrice, $openPrice);

        if ($this->marketIds[$baseCurrency]['show_in_home'] ?? false) {
            MarketPriceUpdated::dispatch([
                'base_currency' => $baseCurrency,
                'sell_price' => $prices['sell_price'],
                'sell_open_price' => $prices['sell_open_price'],
                'buy_price' => $prices['buy_price'],
                'buy_open_price' => $prices['buy_open_price'],
                'last_price' => $lastPrice,
                'price_change_percentage' => $priceChangePercentage,
                'timestamp' => now()->timestamp,
            ]);
        }

        MarketUpdated::dispatch($marketId, [
            'low' => $data['low'] ?? null,
            'high' => $data['high'] ?? null,
            'last' => $lastPrice,
            'open' => $openPrice,
            'exchange_sell_price' => $prices['sell_price'],
            'exchange_buy_price' => $prices['buy_price'],
            'exchange_profit_sell' => $profits['sell'],
            'exchange_profit_buy' => $profits['buy'],
            'price_change_percentage' => $priceChangePercentage,
        ]);
    }

    private function getMarketIdForBaseCurrency(string $baseCurrency): ?int
    {
        if (isset($this->marketIds[$baseCurrency]['id']) && time() - $this->marketIds[$baseCurrency]['last_update'] <= 60) {
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
            'show_in_home' => (bool) $market->show_in_home,
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
            ->where('exchange_id', $this->binanceID)
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
            ->where('exchange_id', $this->binanceID)
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
        $markets = Market::query()
            ->where('price_update_enabled', true)
            ->whereHas('activeExchangePrice', function ($query) {
                $query->where('exchange_id', $this->binanceID);
            })->pluck('base_currency')->map(fn($market) => $market . 'USDT')
            ->toArray();

        if (count($markets) > self::MAX_STREAMS) {
            echo count($markets) . ' markets exceed the ' . self::MAX_STREAMS
                . " stream limit of a single connection. Subscribing to the first " . self::MAX_STREAMS . ".\n";
            $markets = array_slice($markets, 0, self::MAX_STREAMS);
        }

        return $markets;
    }

    private function checkForMarketChanges($loop, WebSocket $stream): void
    {
        $newMarkets = $this->fetchMarkets();

        if ($newMarkets === $this->currentMarkets) {
            return;
        }

        echo "Market list updated. Resubscribing...\n";

        $marketsToUnsubscribe = array_diff($this->currentMarkets, $newMarkets);
        $marketsToSubscribe = array_diff($newMarkets, $this->currentMarkets);
        $this->currentMarkets = $newMarkets;

        $this->sendStreamCommand($loop, $stream, 'UNSUBSCRIBE', $marketsToUnsubscribe);
        $this->sendStreamCommand($loop, $stream, 'SUBSCRIBE', $marketsToSubscribe);
    }

    private function sendStreamCommand($loop, WebSocket $stream, string $method, array $markets): void
    {
        if (empty($markets)) {
            echo "No markets to {$method}.\n";
            return;
        }

        // Stream names have to be lowercase; @ticker carries both the last price
        // and the 24h open price this panel stores.
        $streams = array_map(fn($market) => strtolower($market) . '@ticker', $markets);

        foreach (array_chunk($streams, self::STREAMS_PER_MESSAGE) as $params) {
            $payload = json_encode([
                'method' => $method,
                'params' => $params,
                'id' => $this->requestId++,
            ]);

            $delay = max(0, $this->nextSendAt - microtime(true));
            $this->nextSendAt = microtime(true) + $delay + self::SECONDS_BETWEEN_MESSAGES;

            if ($delay <= 0) {
                $stream->send($payload);
                continue;
            }

            $this->activeTimers[] = $loop->addTimer($delay, fn() => $stream->send($payload));
        }

        echo $method . ' Binance markets: ' . implode(', ', $markets) . "\n";
    }
}

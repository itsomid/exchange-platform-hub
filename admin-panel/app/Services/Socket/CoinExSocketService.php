<?php

namespace App\Services\Socket;

use App\Models\Exchange;
use App\Models\ExchangePrice;
use App\Models\Market;
use Exception;
use Ratchet\Client\WebSocket;
use Ratchet\RFC6455\Messaging\MessageInterface;
use Throwable;

class CoinExSocketService
{
    private string $socketUrl = 'wss://socket.coinex.com/v2/spot';

    private array $coinsPrice = [];
    private ?int $coinexID = null;
    private array $marketIds = [];

    public function startListener(): void
    {
        $reactConnector = new \React\Socket\Connector([
            'dns' => '1.1.1.1',
            'timeout' => 10,
        ]);
        $loop = \React\EventLoop\Loop::get();
        $connector = new \Ratchet\Client\Connector($loop, $reactConnector);

        $connector($this->socketUrl)
            ->then(
                function (WebSocket $conn) {
                    echo "Connected to WebSocket\n";

                    // Send subscription message
                    $subscribeMessage = [
                        'method' => 'state.subscribe',
                        'params' => ['market_list' => ['USDTUSDT','BTCUSDT', 'ETHUSDT', 'DOGEUSDT', 'TRXUSDT', 'BNBUSDT']],
                        'id' => 1,
                    ];
                    $conn->send(json_encode($subscribeMessage));

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
                                echo 'Invalid JSON format: '.json_last_error_msg()."\n";

                                return;
                            }

                            $this->processMessage($decodedData);
                        } catch (Throwable $e) {
                            echo 'Error processing message: '.$e->getMessage()."\n";
                        }
                    });

                    // Handle connection close
                    $conn->on('close', function ($code = null, $reason = null) {
                        echo "WebSocket closed: {$code} - {$reason}\n";
                    });
                },
                function (Exception $e) {
                    echo "Could not connect to WebSocket: {$e->getMessage()}\n";
                }
            );

        $loop->run();
    }

    private function processMessage(array $data): void
    {
        // Check for the "state.update" method
        if (isset($data['method']) && $data['method'] === 'state.update') {
            $markets = $data['data']['state_list'] ?? [];
            foreach ($markets as $market) {
                $this->updateCurrencyPrice($market['market'], $market['last'] ?? null, $market['open'] ?? null);
            }
        }
    }

    private function decompressMessage(string $data): ?string
    {
        // Attempt gzinflate (raw deflate)
        $message = @gzinflate($data);
        if ($message !== false) {
            return $message;
        }

        // Attempt gzdecode (gzip)
        $message = @gzdecode($data);
        if ($message !== false) {
            return $message;
        }

        // Attempt zlib_decode
        $message = @zlib_decode($data);
        if ($message !== false) {
            return $message;
        }

        return $data; // Decompression failed
    }

    private function updateCurrencyPrice(string $symbol, ?string $lastPrice, ?string $openPrice): void
    {
        $baseCurrency = str_replace('USDT', '', $symbol);

        if (
            ! isset($this->coinsPrice[$baseCurrency]) ||
            $this->coinsPrice[$baseCurrency]['last'] !== $lastPrice ||
            $this->coinsPrice[$baseCurrency]['open'] !== $openPrice
        ) {
            echo $baseCurrency.': last->'.$lastPrice.PHP_EOL;
            echo $baseCurrency.': open->'.$openPrice.PHP_EOL;

            // Update cached prices
            $this->coinsPrice[$baseCurrency] = [
                'last' => $lastPrice,
                'open' => $openPrice,
            ];

            if(is_null($this->coinexID)){
                $coinExExchange = Exchange::query()
                    ->where('slug', 'coinex')
                    ->where('is_active', true)
                    ->first();
                $this->coinexID = $coinExExchange->id;
            }
            if(!isset($this->marketIds[$baseCurrency])){
                $market = Market::query()
                    ->where('base_currency', $baseCurrency)
                    ->first();
                $this->marketIds[$baseCurrency] = $market->id;
            }
                // Find the exchange price related to the market for CoinEx
            ExchangePrice::query()
                ->where('market_id', $this->marketIds[$baseCurrency])
                ->where('exchange_id', $this->coinexID)
                ->update([
                    'price' => $lastPrice,
                    'open_price' => $openPrice,
                ]);


        }
    }
}

<?php

namespace App\Services\Socket;

use App\Models\Market;
use Throwable;
use WebSocket\Client;

class CoinExSocketService
{
    private string $socketUrl = 'wss://socket.coinex.com/';

    private array $coinsPrice = [];

    public function startListener(): void
    {
        while (true) {
            try {
                $this->listenToSocket();
            } catch (Throwable $e) {
                report($e);
                // Log unexpected errors
                echo $e->getMessage();
            }
        }
    }

    private function listenToSocket(): void
    {
        $client = new Client($this->socketUrl, ['timeout' => 60]); // Set a timeout

        // Subscribe to a channel
        $subscribeMessage = [
            'method' => 'state.subscribe',
            'params' => ['BTCUSDT', 'ETHUSDT', 'DOGEUSDT', 'TRXUSDT', 'BNBUSDT'],
            'id' => 1,
        ];
        $client->send(json_encode($subscribeMessage));

        while (true) {
            $message = $client->receive();
            $data = json_decode($message, true);

            // Process the WebSocket message
            $this->processMessage($data);
        }
    }

    private function processMessage(array $data): void
    {
        // Check for the "state.update" method
        if (isset($data['method']) && $data['method'] === 'state.update') {
            $prices = $data['params'][0] ?? [];
            foreach ($prices as $symbol => $details) {
                $this->updateCurrencyPrice($symbol, $details['last'] ?? null, $details['open'] ?? null);
            }
        }
    }

    private function updateCurrencyPrice(string $symbol, ?string $lastPrice, ?string $openPrice): void
    {
        $baseCurrent = str_replace('USDT', '', $symbol);

        if (
            !isset($this->coinsPrice[$baseCurrent]) ||
            $this->coinsPrice[$baseCurrent]['last'] !== $lastPrice ||
            $this->coinsPrice[$baseCurrent]['open'] !== $openPrice
        ) {
            echo $baseCurrent . ': last->' . $lastPrice . PHP_EOL;
            echo $baseCurrent . ': open->' . $openPrice . PHP_EOL;

            // Update cached prices
            $this->coinsPrice[$baseCurrent] = [
                'last' => $lastPrice,
                'open' => $openPrice,
            ];

            // Update the database
            Market::query()
                ->where('base_currency', $baseCurrent)
                ->where('quote_currency', 'USDT')
                ->update([
                    'price' => $lastPrice,
                    'open_price' => $openPrice,
                ]);
        }
    }
}

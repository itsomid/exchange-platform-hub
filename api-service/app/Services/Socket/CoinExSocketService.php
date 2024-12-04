<?php

namespace App\Services\Socket;

use App\Models\Market;
use WebSocket\Client;
use WebSocket\ConnectionException;

class CoinExSocketService
{
    private string $socketUrl = 'wss://socket.coinex.com/';

    private int $reconnectDelay = 1; // Delay in seconds before retrying

    public function startListener(): void
    {
        while (true) {
            try {
                $this->listenToSocket();
            } catch (\Exception $e) {
                report($e);
                // Log unexpected errors
                echo $e->getMessage();
                break; // Exit loop on critical errors
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

    private function processMessage(array $data)
    {
        // Check for the "state.update" method
        if (isset($data['method']) && $data['method'] === 'state.update') {
            $prices = $data['params'][0] ?? [];
            foreach ($prices as $symbol => $details) {
                $this->updateCurrencyPrice($symbol, $details['last'] ?? null);
            }
        }
    }

    private function updateCurrencyPrice(string $symbol, ?string $lastPrice): void
    {
        if ($lastPrice) {
            $baseCurrent = str_replace('USDT', '', $symbol);
            Market::query()
                ->where('base_currency', $baseCurrent)
                ->where('quote_currency', 'USDT')
                ->update(['price' => $lastPrice]);
        }
    }
}

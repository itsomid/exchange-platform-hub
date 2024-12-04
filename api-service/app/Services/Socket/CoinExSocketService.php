<?php

namespace App\Services\Socket;

use App\Models\Market;
use WebSocket\Client;

class CoinExSocketService
{
    private string $socketUrl = 'wss://socket.coinex.com/';

    public function startListener()
    {
        $client = new Client($this->socketUrl);

        // Subscribe to a channel (replace with your actual subscription message)
        $subscribeMessage = [
            'method' => 'state.subscribe',
            'params' => ['BTCUSDT'],
            'id' => 1,
        ];
        $client->send(json_encode($subscribeMessage));

        while (true) {
            try {
                $message = $client->receive();
                $data = json_decode($message, true);

                // Process the WebSocket message
                $this->processMessage($data);
            } catch (\Exception $e) {
                dd($e->getMessage());
                // Log the error and attempt reconnection
                logger()->error('WebSocket error: '.$e->getMessage());
                break;
            }
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
                ->where('symbol', $symbol)
                ->update(['price' => $lastPrice]);
        }
    }
}

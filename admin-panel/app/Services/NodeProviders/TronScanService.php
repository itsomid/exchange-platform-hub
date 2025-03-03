<?php

namespace App\Services\NodeProviders;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;

class TronScanService
{
    protected $baseUrl = 'https://apilist.tronscanapi.com/api/account/tokens';
    protected $contractAddress = 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t'; // USDT contract on BSC

    /**
     * Get the USDT balance for a given wallet address on BSC.
     *
     * @param string $address The wallet address to query
     * @return array ['amount' => string, 'unit' => string] or ['error' => string]
     */
    public function getUsdtBalance(string $address)
    {
        $apiKey = Config::get('tronscan.api_key');

        $params = [
            'address' => $address,
            'token' => 'USDT',
        ];

        try {
            $response = Http::get($this->baseUrl, $params);

            if ($response->successful()) {
                $data = $response->json();

                // Check if the data array is present and has items
                if (isset($data['data']) && is_array($data['data']) && count($data['data']) > 0) {
                    $balance = $data['data'][0]['balance']; // Extract the balance
                    $tokenDecimal = $data['data'][0]['tokenDecimal']; // Extract the tokenDecimal

                    // Convert the balance to a human-readable format
                    $balance = bcdiv($balance, bcpow('10', $tokenDecimal), $tokenDecimal);

                    return [
                        'amount' => $balance
                    ];
                } else {
                    return [
                        'error' => 'No balance data found'
                    ];
                }
            }

            return [
                'error' => 'API request failed',
                'details' => $response->body()
            ];
        } catch (\Exception $e) {
            return [
                'error' => 'API request error',
                'details' => $e->getMessage()
            ];
        }
    }
}

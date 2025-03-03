<?php

namespace App\Services\NodeProviders;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;

class BscScanService
{
    protected $baseUrl = 'https://api.bscscan.com/api';
    protected $contractAddress = '0x55d398326f99059fF775485246999027B3197955'; // USDT contract on BSC

    /**
     * Get the USDT balance for a given wallet address on BSC.
     *
     * @param string $address The wallet address to query
     * @return array ['amount' => string, 'unit' => string] or ['error' => string]
     */
    public function getUsdtBalance(string $address)
    {
        $apiKey = Config::get('bscscan.api_key');

        $params = [
            'module' => 'account',
            'action' => 'tokenbalance',
            'address' => $address,
            'contractaddress' => $this->contractAddress,
            'apikey' => $apiKey,
        ];

        try {
            $response = Http::get($this->baseUrl, $params);

            if ($response->successful()) {
                $data = $response->json();

                if ($data['status'] === '1') {
                    $balanceWei = $data['result']; // Balance in smallest unit (10^18 decimals)
                    $balance = bcdiv($balanceWei, bcpow('10', '18'), 5); // Convert to USDT
                    return [
                        'amount' => $balance
                    ];
                } else {
                    return [
                        'error' => $data['message'] ?? 'Unknown error'
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

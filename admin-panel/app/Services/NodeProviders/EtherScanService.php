<?php

namespace App\Services\NodeProviders;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;

class EtherScanService
{
    protected $baseUrl = 'https://api.etherscan.io/v2/api';
    protected $contractAddress = '0xdAC17F958D2ee523a2206206994597C13D831ec7'; // USDT contract on ETHEREUM

    /**
     * Get the USDT balance for a given wallet address on ETHEREUM.
     *
     * @param string $address The wallet address to query
     * @return array ['amount' => string, 'unit' => string] or ['error' => string]
     */
    public function getUsdtBalance(string $address)
    {
        $apiKey = Config::get('etherscan.api_key');

        $params = [
            'chainid' => 1,
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
                    $balanceWei = $data['result']; // Balance in smallest unit
                    $balance = bcdiv($balanceWei, bcpow('10', 6), 6); // Convert to USDT (6 decimal places)
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

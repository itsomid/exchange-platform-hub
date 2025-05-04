<?php

namespace App\Services\NodeProviders;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;

class BlockchairService
{
    protected $baseUrl = 'https://api.blockchair.com';


    public function getBalance(string $currency_symbol, string $address)
    {
        $apiKey = Config::get('blockchair.api_key');

        // Determine the blockchain based on currency symbol
        $blockchain = $this->getBlockchainForCurrency($currency_symbol);

        try {
            $response = Http::get("{$this->baseUrl}/{$blockchain}/dashboards/address/{$address}", [
                'key' => $apiKey
            ]);

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['data'][$address])) {
                    $addressData = $data['data'][$address];

                    // Extract balance based on currency
                    $balance = $this->extractBalanceFromResponse($currency_symbol, $addressData);

                    return [
                        'amount' => $balance
                    ];
                }

                return [
                    'error' => 'Address not found in response',
                    'details' => $data
                ];
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

    /**
     * Map currency symbol to blockchain name for Blockchair API
     */
    private function getBlockchainForCurrency(string $currency_symbol): string
    {
        $mapping = [
            'BTC' => 'bitcoin',
            'ETH' => 'ethereum',
            'DOGE' => 'dogecoin',
            'LTC' => 'litecoin',
            'BCH' => 'bitcoin-cash',
            'BSV' => 'bitcoin-sv',
            'DASH' => 'dash',
            'GRS' => 'groestlcoin',
            'ZEC' => 'zcash',
            'XRP' => 'ripple',
            'XLM' => 'stellar',
            'ADA' => 'cardano',
            'XTZ' => 'tezos',
            'EOS' => 'eos',
            'TRX' => 'tron'
        ];

        return $mapping[$currency_symbol] ?? strtolower($currency_symbol);
    }

    /**
     * Extract balance from response based on currency
     */
    private function extractBalanceFromResponse(string $currency_symbol, array $addressData): string
    {
        // Different currencies might have different response structures
        if ($currency_symbol === 'DOGE') {
            // For DOGE, balance is typically in 'address.balance'
            return isset($addressData['address']['balance'])
                ? bcdiv((string)$addressData['address']['balance'], '100000000', 8)
                : '0';
        } elseif ($currency_symbol === 'TRX') {
            // For TRON, balance might be in a different location
            return isset($addressData['address']['balance'])
                ? bcdiv((string)$addressData['address']['balance'], '1000000', 6)
                : '0';
        }

        // Default case - most blockchains store balance in satoshis (10^8)
        return isset($addressData['address']['balance'])
            ? bcdiv((string)$addressData['address']['balance'], '100000000', 8)
            : '0';
    }
}

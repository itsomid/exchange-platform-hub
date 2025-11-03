<?php

namespace App\Services\NodeProviders;

use App\Models\Currency;
use App\Models\CurrencyChain;
use App\Enums\CurrencyChainEnum;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;

class BscScanService
{
    protected $baseUrl = 'https://api.etherscan.io/v2/api';
    protected $chainId = 56; // BSC chain ID

    /**
     * Token decimal places mapping for common BEP20 tokens
     */
    protected $tokenDecimals = [
        'USDT' => 18,
        'USDC' => 18,
        'BUSD' => 18,
        'BNB' => 10, // Native BNB
        'WBNB' => 18,
        'CAKE' => 18,
        'ADA' => 18,
        'DOT' => 18,
        'LINK' => 18,
        'UNI' => 18,
        'SHIB' => 18,
        'TLM' => 4
    ];

    /**
     * Get the token balance for a given wallet address on BSC.
     *
     * @param string $currency The currency symbol (e.g., 'USDT', 'CAKE', 'BNB')
     * @param string $address The wallet address to query
     * @return array ['amount' => string] or ['error' => string]
     */
    public function getBalance(string $currency, string $address)
    {
        $currency = strtoupper($currency);

        // Check if it's native BNB
        if ($currency === 'BNB') {
            return $this->getBnbBalance($address);
        }

        // For BEP20 tokens, get contract address and query token balance
        $contractAddress = $this->getContractAddress($currency);
        if (!$contractAddress) {
            return [
                'error' => "Contract address not found for currency: {$currency}"
            ];
        }

        $apiKey = Config::get('etherscan.api_key');
        $decimals = $this->tokenDecimals[$currency] ?? 18;

        $params = [
            'chainid' => $this->chainId,
            'module' => 'account',
            'action' => 'tokenbalance',
            'address' => $address,
            'contractaddress' => $contractAddress,
            'apikey' => $apiKey,
        ];

        try {
            $response = Http::get($this->baseUrl, $params);

            if ($response->successful()) {
                $data = $response->json();

                if ($data['status'] === '1') {
                    $balanceWei = $data['result']; // Balance in smallest unit

                    $balance = bcdiv($balanceWei, bcpow('10', $decimals), $decimals);
                    return [
                        'amount' => $balance
                    ];
                } else {
                    return [
                        'error' => $data['result'] ?? ($data['message'] ?? 'Unknown error')
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

    /**
     * Get native BNB balance
     */
    protected function getBnbBalance(string $address)
    {
        $apiKey = Config::get('etherscan.api_key');

        $params = [
            'chainid' => $this->chainId,
            'module' => 'account',
            'action' => 'balance',
            'address' => $address,
            'apikey' => $apiKey,
        ];

        try {
            $response = Http::get($this->baseUrl, $params);

            if ($response->successful()) {
                $data = $response->json();

                if ($data['status'] === '1') {
                    $balanceWei = $data['result']; // Balance in Wei (10^18)

                    $balance = bcdiv($balanceWei, bcpow('10', '18'), 18); // Convert to BNB
                    return [
                        'amount' => $balance
                    ];
                } else {
                    return [
                        'error' => $data['result'] ?? ($data['message'] ?? 'Unknown error')
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

    /**
     * Get contract address for a given currency
     */
    protected function getContractAddress(string $currency): ?string
    {
        // First try to get from database
        try {
            $currencyModel = Currency::where('symbol', $currency)->first();
            if ($currencyModel) {
                $currencyChain = CurrencyChain::where('currency_id', $currencyModel->id)
                    ->where('chain', CurrencyChainEnum::BSC)
                    ->first();

                if ($currencyChain && $currencyChain->contract_address) {
                    return $currencyChain->contract_address;
                }
            }
        } catch (\Exception $e) {
            // Log error but continue with fallback
        }


        return null;
    }

    /**
     * Get token decimals for a given currency
     */
    protected function getTokenDecimals(string $currency): int
    {
        // First try to get from database
        try {
            $currencyModel = Currency::where('symbol', $currency)->first();
            if ($currencyModel) {
                $currencyChain = CurrencyChain::where('currency_id', $currencyModel->id)
                    ->where('chain', CurrencyChainEnum::BSC)
                    ->first();

                if ($currencyChain && $currencyChain->withdrawal_precision) {
                    return $currencyChain->withdrawal_precision;
                }
            }
        } catch (\Exception $e) {
            // Log error but continue with fallback
        }

        // Fallback to hardcoded mapping
        return $this->tokenDecimals[$currency] ?? 18; // Default to 18 decimals for most BEP20 tokens
    }
}

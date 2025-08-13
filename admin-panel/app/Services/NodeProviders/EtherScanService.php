<?php

namespace App\Services\NodeProviders;

use App\Models\Currency;
use App\Models\CurrencyChain;
use App\Enums\CurrencyChainEnum;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;

class EtherScanService
{
    protected $baseUrl = 'https://api.etherscan.io/v2/api';

    /**
     * Token decimal places mapping for common ERC20 tokens
     */
    protected $tokenDecimals = [
        'USDT' => 6,
        'AUDIO' => 18,
        'USDC' => 6,
        'DAI' => 18,
        'WETH' => 18,
        'LINK' => 18,
        'UNI' => 18,
        'AAVE' => 18,
        'COMP' => 18,
        'MKR' => 18,
        'SNX' => 18,
        'YFI' => 18,
        'SUSHI' => 18,
        'CRV' => 18,
        'BAL' => 18,
        'MATIC' => 18,
        'ETH' => 18, // Native ETH
        'TLM' => 4
    ];

    /**
     * Get the token balance for a given wallet address on ETHEREUM.
     *
     * @param string $currency The currency symbol (e.g., 'USDT', 'AUDIO', 'ETH')
     * @param string $address The wallet address to query
     * @return array ['amount' => string] or ['error' => string]
     */
    public function getBalance(string $currency, string $address)
    {
        $apiKey = Config::get('etherscan.api_key');
        $currency = strtoupper($currency);

        // Check if it's native ETH
        if ($currency === 'ETH') {
            return $this->getEthBalance($address, $apiKey);
        }

        // Get contract address for the token
        $contractAddress = $this->getContractAddress($currency);

        if (!$contractAddress) {
            return [
                'error' => "Contract address not found for currency: {$currency}"
            ];
        }

        // Get token decimals
        $decimals = $this->tokenDecimals[$currency] ?? 18;

        $params = [
            'chainid' => 1,
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

    /**
     * Get native ETH balance
     */
    protected function getEthBalance(string $address, string $apiKey)
    {
        $params = [
            'chainid' => 1,
            'module' => 'account',
            'action' => 'balance',
            'address' => $address,
            'tag' => 'latest',
            'apikey' => $apiKey,
        ];

        try {
            $response = Http::get($this->baseUrl, $params);

            if ($response->successful()) {
                $data = $response->json();

                if ($data['status'] === '1') {
                    $balanceWei = $data['result']; // Balance in wei
                    $balance = bcdiv($balanceWei, bcpow('10', 18), 18); // Convert to ETH (18 decimal places)
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

    /**
     * Get contract address for a given currency
     */
    protected function getContractAddress(string $currency): ?string
    {
        // First try to get from database
        try {
            $currency = Currency::where('symbol', $currency)->first();
            if ($currency) {
                $currencyChain = CurrencyChain::where('currency_id', $currency->id)
                    ->where('chain', CurrencyChainEnum::ERC20)
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
}

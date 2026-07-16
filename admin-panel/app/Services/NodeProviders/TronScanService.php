<?php

namespace App\Services\NodeProviders;

use App\Models\Currency;
use App\Models\CurrencyChain;
use App\Enums\CurrencyChainEnum;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;

class TronScanService
{
    protected $baseUrl = 'https://apilist.tronscanapi.com/api';

    /**
     * Token decimal places mapping for common TRC20 tokens
     */
    protected $tokenDecimals = [
        'USDT' => 6,
        'USDC' => 6,
        'USDD' => 18,
        'BTT' => 18,
        'WIN' => 6,
        'SUN' => 18,
        'JST' => 18,
        'TRX' => 6, // Native TRX
        'WTRX' => 6,
        'TUSD' => 18,
        'USDJ' => 18,
        'WIN' => 6,
    ];

    /**
     * Get the token balance for a given wallet address on TRON.
     *
     * @param string $currency The currency symbol (e.g., 'USDT', 'BTT', 'TRX')
     * @param string $address The wallet address to query
     * @return array ['amount' => string] or ['error' => string]
     */
    public function getBalance(string $currency, string $address)
    {
        $currency = strtoupper($currency);

        // Check if it's native TRX
        if ($currency === 'TRX') {
            return $this->getTrxBalance($address);
        }

        // For TRC20 tokens, use the account/tokens endpoint
        return $this->getTokenBalance($currency, $address);
    }

    /**
     * Get native TRX balance
     */
    protected function getTrxBalance(string $address)
    {
        $url = $this->baseUrl . '/account';
        $params = [
            'address' => $address,
        ];

        try {
            $response = Http::withHeaders([
                'TRON-PRO-API-KEY' => Config::get('tronscan.api_key')
            ])->get($url, $params);

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['balance'])) {
                    // TRX balance is returned in SUN (1 TRX = 1,000,000 SUN)
                    $balanceSun = $data['balance'];
                    $balance = bcdiv($balanceSun, bcpow('10', 6), 6); // Convert to TRX (6 decimal places)
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

    /**
     * Get TRC20 token balance
     */
    protected function getTokenBalance(string $currency, string $address)
    {
        $url = $this->baseUrl . '/account/tokens';
        $params = [
            'address' => $address,
            'token' => $currency,
        ];

        try {
            $response = Http::withHeaders([
                'TRON-PRO-API-KEY' => Config::get('tronscan.api_key')
            ])->get($url, $params);

            if ($response->successful()) {
                $data = $response->json();

                // Check if the data array is present and has items
                if (isset($data['data']) && is_array($data['data']) && count($data['data']) > 0) {
                    $tokenData = $data['data'][0];
                    $balance = $tokenData['balance']; // Extract the balance
                    $tokenDecimal = $tokenData['tokenDecimal'] ?? $this->tokenDecimals[$currency] ?? 6;; // Extract the tokenDecimal

                    // Convert the balance to a human-readable format
                    $balance = bcdiv($balance, bcpow('10', $tokenDecimal), $tokenDecimal);

                    return [
                        'amount' => $balance
                    ];
                }

                // Empty data means the address does not hold this token → balance is zero.
                return [
                    'amount' => '0'
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
     * Get token balance using contract address (fallback method)
     */
    protected function getTokenBalanceByContract(string $currency, string $address)
    {
        $contractAddress = $this->getContractAddress($currency);
        if (!$contractAddress) {
            return [
                'error' => "Contract address not found for currency: {$currency}"
            ];
        }

        $url = $this->baseUrl . '/contract/trigger-smart-contract';
        $params = [
            'contract_address' => $contractAddress,
            'function_selector' => 'balanceOf(address)',
            'parameter' => $address,
            'owner_address' => $address,
        ];

        try {
            $response = Http::withHeaders([
                'TRON-PRO-API-KEY' => Config::get('tronscan.api_key')
            ])->post($url, $params);

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['constant_result']) && is_array($data['constant_result']) && count($data['constant_result']) > 0) {
                    $balanceHex = $data['constant_result'][0];
                    $balanceWei = hexdec($balanceHex);
                    $decimals = $this->tokenDecimals[$currency] ?? 6;;
                    $balance = bcdiv($balanceWei, bcpow('10', $decimals), $decimals);

                    return [
                        'amount' => $balance
                    ];
                }
            }

            $reason = !$response->successful()
                ? 'HTTP ' . $response->status() . ': ' . $response->body()
                : 'Missing or empty constant_result in response: ' . json_encode($response->json());

            return [
                'error' => 'Unable to fetch token balance',
                'details' => $reason,
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
                    ->where('chain', CurrencyChainEnum::TRC20)
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
                    ->where('chain', CurrencyChainEnum::TRC20)
                    ->first();

                if ($currencyChain && $currencyChain->withdrawal_precision) {
                    return $currencyChain->withdrawal_precision;
                }
            }
        } catch (\Exception $e) {
            // Log error but continue with fallback
        }

        // Fallback to hardcoded mapping
        return $this->tokenDecimals[$currency] ?? 6; // Default to 6 decimals for most TRC20 tokens
    }

    /**
     * Get outgoing (sent) transactions for a given address and currency.
     * This fetches TRC20 token transfers or native TRX transfers where the address is the sender.
     *
     * @param string $currency The currency symbol (e.g., 'USDT', 'TRX')
     * @param string $address The wallet address to query
     * @param int|null $startTimestamp Only get transactions after this timestamp (milliseconds)
     * @return array ['transactions' => array] or ['error' => string]
     */
    public function getOutgoingTransactions(string $currency, string $address, ?int $startTimestamp = null): array
    {
        $currency = strtoupper($currency);

        if ($currency === 'TRX') {
            return $this->getTrxOutgoingTransactions($address, $startTimestamp);
        }

        return $this->getTrc20OutgoingTransactions($currency, $address, $startTimestamp);
    }

    /**
     * Get native TRX outgoing transactions
     */
    protected function getTrxOutgoingTransactions(string $address, ?int $startTimestamp = null): array
    {
        $url = $this->baseUrl . '/transaction';
        $params = [
            'address' => $address,
            'limit' => 200,
            'sort' => '-timestamp',
        ];

        if ($startTimestamp) {
            $params['start_timestamp'] = $startTimestamp;
        }

        try {
            $response = Http::withHeaders([
                'TRON-PRO-API-KEY' => Config::get('tronscan.api_key')
            ])->get($url, $params);

            if ($response->successful()) {
                $data = $response->json();
                $transactions = [];

                if (isset($data['data']) && is_array($data['data'])) {
                    foreach ($data['data'] as $tx) {
                        // Only include outgoing transactions (where this address is the sender)
                        if (isset($tx['ownerAddress']) && strtolower($tx['ownerAddress']) === strtolower($address)) {
                            // Filter only TRX transfer transactions
                            if (isset($tx['contractType']) && $tx['contractType'] === 1) { // TransferContract
                                $amount = isset($tx['amount']) ? bcdiv((string)$tx['amount'], bcpow('10', '6'), 6) : '0';
                                
                                $transactions[] = [
                                    'transaction_hash' => $tx['hash'] ?? '',
                                    'from_address' => $tx['ownerAddress'] ?? '',
                                    'to_address' => $tx['toAddress'] ?? '',
                                    'amount' => $amount,
                                    'block_number' => $tx['block'] ?? null,
                                    'transaction_at' => isset($tx['timestamp']) ? (int)($tx['timestamp'] / 1000) : null,
                                ];
                            }
                        }
                    }
                }

                return ['transactions' => $transactions];
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
     * Get TRC20 token outgoing transactions
     */
    protected function getTrc20OutgoingTransactions(string $currency, string $address, ?int $startTimestamp = null): array
    {
        $contractAddress = $this->getContractAddress($currency);
        
        $url = $this->baseUrl . '/token_trc20/transfers';
        $params = [
            'relatedAddress' => $address,
            'limit' => 200,
            'sort' => '-timestamp',
        ];

        if ($contractAddress) {
            $params['contract_address'] = $contractAddress;
        } else {
            // If no contract address, filter by token name
            $params['tokenName'] = $currency;
        }

        if ($startTimestamp) {
            $params['start_timestamp'] = $startTimestamp;
        }

        try {
            $response = Http::withHeaders([
                'TRON-PRO-API-KEY' => Config::get('tronscan.api_key')
            ])->get($url, $params);

            if ($response->successful()) {
                $data = $response->json();
                $transactions = [];

                if (isset($data['token_transfers']) && is_array($data['token_transfers'])) {
                    foreach ($data['token_transfers'] as $tx) {
                        // Only include outgoing transactions (where this address is the sender)
                        if (isset($tx['from_address']) && strtolower($tx['from_address']) === strtolower($address)) {
                            $decimals = $tx['tokenInfo']['tokenDecimal'] ?? $this->tokenDecimals[$currency] ?? 6;
                            $amount = isset($tx['quant']) ? bcdiv((string)$tx['quant'], bcpow('10', (string)$decimals), $decimals) : '0';
                            
                            $transactions[] = [
                                'transaction_hash' => $tx['transaction_id'] ?? '',
                                'from_address' => $tx['from_address'] ?? '',
                                'to_address' => $tx['to_address'] ?? '',
                                'amount' => $amount,
                                'block_number' => $tx['block'] ?? null,
                                'transaction_at' => isset($tx['block_ts']) ? (int)($tx['block_ts'] / 1000) : null,
                            ];
                        }
                    }
                }

                return ['transactions' => $transactions];
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

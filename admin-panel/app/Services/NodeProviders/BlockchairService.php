<?php

namespace App\Services\NodeProviders;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;
use Opcodes\LogViewer\Logs\Log;

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
                    $balance = $this->extractBalanceFromResponse($addressData);

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
            'DASH' => 'dash',
            'BCH' => 'bitcoin-cash',
            'BSV' => 'bitcoin-sv',
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
    private function extractBalanceFromResponse(array $addressData): string
    {
        // Default case - most blockchains store balance in satoshis (10^8)
        return isset($addressData['address']['balance'])
            ? bcdiv((string)$addressData['address']['balance'], '100000000', 8)
            : '0';
    }

    /**
     * Get outgoing (sent) transactions for a given address.
     * This fetches transactions where the address is an input (sender).
     *
     * @param string $currency The currency symbol (e.g., 'BTC', 'DOGE', 'LTC')
     * @param string $address The wallet address to query
     * @param int|null $afterBlockHeight Only get transactions after this block height
     * @return array ['transactions' => array] or ['error' => string]
     */
    public function getOutgoingTransactions(string $currency, string $address, ?int $afterBlockHeight = null): array
    {
        $apiKey = Config::get('blockchair.api_key');
        $blockchain = $this->getBlockchainForCurrency($currency);

        try {
            // Use the address dashboard with transaction details to get balance_change
            $response = Http::get("{$this->baseUrl}/{$blockchain}/dashboards/address/{$address}", [
                'key' => $apiKey,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $transactions = [];

                if (isset($data['data'][$address]['transactions']) && is_array($data['data'][$address]['transactions'])) {
                    $txList = $data['data'][$address]['transactions'];
                    
                    // If transactions are just hashes (strings), fetch full details
                    if (count($txList) > 0 && is_string($txList[0])) {
                        
                        // Fetch details for each transaction in batches
                        $batchSize = 10;
                        $batches = array_chunk($txList, $batchSize);
                        
                        foreach ($batches as $batch) {
                            $txDetails = $this->getTransactionDetails($blockchain, $batch, $apiKey);
                            
                            foreach ($txDetails as $txHash => $tx) {
                                // Skip if transaction data is not in expected format
                                if (!is_array($tx) || !isset($tx['transaction'])) {
                                    continue;
                                }
                                
                                // Check if this address is an input (sender)
                                $isOutgoing = false;
                                $totalSent = '0';
                                $toAddress = null;
                                
                                if (isset($tx['inputs']) && is_array($tx['inputs'])) {
                                    foreach ($tx['inputs'] as $input) {
                                        if (isset($input['recipient']) && strtolower($input['recipient']) === strtolower($address)) {
                                            $isOutgoing = true;
                                            $totalSent = bcadd($totalSent, bcdiv((string)($input['value'] ?? 0), '100000000', 8), 8);
                                        }
                                    }
                                }

                                // Get main output address (excluding change back to sender)
                                if ($isOutgoing && isset($tx['outputs']) && is_array($tx['outputs'])) {
                                    foreach ($tx['outputs'] as $output) {
                                        if (isset($output['recipient']) && strtolower($output['recipient']) !== strtolower($address)) {
                                            $toAddress = $output['recipient'];
                                            break;
                                        }
                                    }
                                }

                                if ($isOutgoing && bccomp($totalSent, '0', 8) > 0) {
                                    // Filter by block height if specified
                                    $blockHeight = $tx['transaction']['block_id'] ?? null;
                                    if ($afterBlockHeight && $blockHeight && $blockHeight <= $afterBlockHeight) {
                                        continue;
                                    }

                                    $transactions[] = [
                                        'transaction_hash' => $tx['transaction']['hash'] ?? '',
                                        'from_address' => $address,
                                        'to_address' => $toAddress,
                                        'amount' => $totalSent,
                                        'block_number' => $blockHeight,
                                        'transaction_at' => isset($tx['transaction']['time']) ? strtotime($tx['transaction']['time']) : null,
                                    ];
                                }
                            }
                            
                            // Add delay between batches to respect rate limits
                            if (count($batches) > 1) {
                                usleep(500000); // 500ms delay
                            }
                        }
                    } else {
                        // Old format with balance_change (shouldn't happen now but keep for safety)
                        foreach ($txList as $tx) {
                            if (is_array($tx) && isset($tx['balance_change'])) {
                                if ($tx['balance_change'] < 0) {
                                    $blockHeight = $tx['block_id'] ?? null;
                                    
                                    if ($afterBlockHeight && $blockHeight && $blockHeight <= $afterBlockHeight) {
                                        continue;
                                    }
                                    
                                    $amount = bcdiv((string)abs($tx['balance_change']), '100000000', 8);
                                    
                                    $transactions[] = [
                                        'transaction_hash' => $tx['hash'] ?? '',
                                        'from_address' => $address,
                                        'to_address' => null,
                                        'amount' => $amount,
                                        'block_number' => $blockHeight,
                                        'transaction_at' => isset($tx['time']) ? strtotime($tx['time']) : null,
                                    ];
                                }
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
            $errorMessage = $e->getMessage();
            // Handle case where getMessage() returns an array or object
            if (!is_string($errorMessage)) {
                $errorMessage = json_encode($errorMessage);
            }
            return [
                'error' => 'API request error',
                'details' => $errorMessage
            ];
        }
    }

    /**
     * Get details for multiple transactions
     */
    private function getTransactionDetails(string $blockchain, array $txHashes, string $apiKey): array
    {
        if (empty($txHashes)) {
            return [];
        }

        // Filter out any non-string values from txHashes
        $txHashes = array_filter($txHashes, fn($hash) => is_string($hash) && !empty($hash));
        
        if (empty($txHashes)) {
            return [];
        }

        $hashesString = implode(',', $txHashes);
        
        try {
            $response = Http::get("{$this->baseUrl}/{$blockchain}/dashboards/transactions/{$hashesString}", [
                'key' => $apiKey,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                // Ensure we return an array of transaction data
                if (!isset($data['data']) || !is_array($data['data'])) {
                    return [];
                }
                
                return $data['data'];
            }
        } catch (\Exception $e) {
            // Log error but return empty array
        }

        return [];
    }
}

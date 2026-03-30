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
        'POL' => 18,
        'ARB' => 18,
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

    /**
     * Get outgoing (sent) transactions for a given address and currency.
     * This fetches ERC20 token transfers or native ETH transfers where the address is the sender.
     *
     * @param string $currency The currency symbol (e.g., 'USDT', 'ETH')
     * @param string $address The wallet address to query
     * @param int|null $startBlock Only get transactions after this block number
     * @return array ['transactions' => array] or ['error' => string]
     */
    public function getOutgoingTransactions(string $currency, string $address, ?int $startBlock = null): array
    {
        $apiKey = Config::get('etherscan.api_key');
        $currency = strtoupper($currency);

        if ($currency === 'ETH') {
            return $this->getEthOutgoingTransactions($address, $apiKey, $startBlock);
        }

        return $this->getErc20OutgoingTransactions($currency, $address, $apiKey, $startBlock);
    }

    /**
     * Get native ETH outgoing transactions
     */
    protected function getEthOutgoingTransactions(string $address, string $apiKey, ?int $startBlock = null): array
    {
        $params = [
            'chainid' => 1,
            'module' => 'account',
            'action' => 'txlist',
            'address' => $address,
            'startblock' => $startBlock ?? 0,
            'endblock' => 99999999,
            'page' => 1,
            'offset' => 200,
            'sort' => 'desc',
            'apikey' => $apiKey,
        ];

        try {
            $response = Http::get($this->baseUrl, $params);

            if ($response->successful()) {
                $data = $response->json();
                $transactions = [];

                if ($data['status'] === '1' && isset($data['result']) && is_array($data['result'])) {
                    foreach ($data['result'] as $tx) {
                        // Only include outgoing transactions (where this address is the sender)
                        // and successful transactions (isError = 0)
                        if (isset($tx['from']) && strtolower($tx['from']) === strtolower($address) && ($tx['isError'] ?? '1') === '0') {
                            $amount = isset($tx['value']) ? bcdiv($tx['value'], bcpow('10', '18'), 18) : '0';

                            // Skip zero-value transactions (contract interactions)
                            if (bccomp($amount, '0', 18) === 0) {
                                continue;
                            }

                            $transactions[] = [
                                'transaction_hash' => $tx['hash'] ?? '',
                                'from_address' => $tx['from'] ?? '',
                                'to_address' => $tx['to'] ?? '',
                                'amount' => $amount,
                                'block_number' => isset($tx['blockNumber']) ? (int)$tx['blockNumber'] : null,
                                'transaction_at' => isset($tx['timeStamp']) ? (int)$tx['timeStamp'] : null,
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

    /**
     * Get gas oracle data (current gas prices)
     *
     * @return array ['SafeGasPrice' => string, 'ProposeGasPrice' => string, 'FastGasPrice' => string] or ['error' => string]
     */
    public function getGasOracle(): array
    {
        $apiKey = Config::get('etherscan.api_key');

        $params = [
            'chainid' => 1,
            'module' => 'gastracker',
            'action' => 'gasoracle',
            'apikey' => $apiKey,
        ];

        try {
            $response = Http::get($this->baseUrl, $params);

            if ($response->successful()) {
                $data = $response->json();

                if ($data['status'] === '1' && isset($data['result'])) {
                    return [
                        'SafeGasPrice' => $data['result']['SafeGasPrice'] ?? '20',
                        'ProposeGasPrice' => $data['result']['ProposeGasPrice'] ?? '30',
                        'FastGasPrice' => $data['result']['FastGasPrice'] ?? '40',
                        'suggestBaseFee' => $data['result']['suggestBaseFee'] ?? '20',
                        'gasUsedRatio' => $data['result']['gasUsedRatio'] ?? null,
                        'UsdPrice' => $data['result']['UsdPrice'] ?? null,
                        'LastBlock' => $data['result']['LastBlock'] ?? null,
                    ];
                }

                return [
                    'error' => $data['message'] ?? 'Failed to get gas oracle',
                ];
            }

            return [
                'error' => 'API request failed',
            ];
        } catch (\Exception $e) {
            return [
                'error' => 'API request error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Estimate gas cost for an ERC20 token transfer
     *
     * @param string $currency Token symbol
     * @param string $gasPriceLevel 'SafeGasPrice', 'ProposeGasPrice', or 'FastGasPrice'
     * @return array ['gasPrice' => string, 'gasLimit' => string, 'totalCostETH' => string] or ['error' => string]
     */
    public function estimateTokenTransferGasCost(string $currency = 'USDT', string $gasPriceLevel = 'ProposeGasPrice'): array
    {
        $currency = strtoupper($currency);

        // Get gas oracle
        $gasOracle = $this->getGasOracle();
        if (isset($gasOracle['error'])) {
            return $gasOracle;
        }

        // Get gas price in Gwei
        $gasPriceGwei = $gasOracle[$gasPriceLevel] ?? $gasOracle['ProposeGasPrice'] ?? '30';
        $gasPriceWei = bcmul($gasPriceGwei, '1000000000', 0); // Convert Gwei to Wei

        // Estimate gas limit for token transfer (typically 65000 for ERC20)
        $gasLimit = '65000';

        // Calculate total cost in Wei
        $totalCostWei = bcmul($gasPriceWei, $gasLimit, 0);

        // Convert to ETH (18 decimals)
        $totalCostETH = bcdiv($totalCostWei, bcpow('10', '18'), 18);

        // Calculate costs for all gas price levels
        $costs = [];
        foreach (['SafeGasPrice', 'ProposeGasPrice', 'FastGasPrice'] as $level) {
            $levelGwei = $gasOracle[$level] ?? '30';
            $levelWei = bcmul($levelGwei, '1000000000', 0);
            $levelCostWei = bcmul($levelWei, $gasLimit, 0);
            $levelCostETH = bcdiv($levelCostWei, bcpow('10', '18'), 18);
            $costs[$level] = [
                'gwei' => $levelGwei,
                'cost' => rtrim(rtrim($levelCostETH, '0'), '.'),
            ];
        }

        return [
            'gasPrice' => $gasPriceGwei, // In Gwei
            'gasPriceWei' => $gasPriceWei,
            'gasLimit' => $gasLimit,
            'totalCostWei' => $totalCostWei,
            'totalCostETH' => rtrim(rtrim($totalCostETH, '0'), '.'),
            'nativeSymbol' => 'ETH',
            'UsdPrice' => $gasOracle['UsdPrice'] ?? null,
            'allLevels' => $costs,
        ];
    }

    /**
     * Get ERC20 token outgoing transactions
     */
    protected function getErc20OutgoingTransactions(string $currency, string $address, string $apiKey, ?int $startBlock = null): array
    {
        $contractAddress = $this->getContractAddress($currency);

        $params = [
            'chainid' => 1,
            'module' => 'account',
            'action' => 'tokentx',
            'address' => $address,
            'startblock' => $startBlock ?? 0,
            'endblock' => 99999999,
            'page' => 1,
            'offset' => 200,
            'sort' => 'desc',
            'apikey' => $apiKey,
        ];

        if ($contractAddress) {
            $params['contractaddress'] = $contractAddress;
        }

        try {
            $response = Http::get($this->baseUrl, $params);

            if ($response->successful()) {
                $data = $response->json();
                $transactions = [];

                if ($data['status'] === '1' && isset($data['result']) && is_array($data['result'])) {
                    foreach ($data['result'] as $tx) {
                        // Only include outgoing transactions (where this address is the sender)
                        if (isset($tx['from']) && strtolower($tx['from']) === strtolower($address)) {
                            // Filter by token symbol if no contract address was specified
                            if (!$contractAddress && isset($tx['tokenSymbol']) && strtoupper($tx['tokenSymbol']) !== $currency) {
                                continue;
                            }

                            $decimals = $tx['tokenDecimal'] ?? $this->tokenDecimals[$currency] ?? 18;
                            $amount = isset($tx['value']) ? bcdiv($tx['value'], bcpow('10', (string)$decimals), (int)$decimals) : '0';

                            $transactions[] = [
                                'transaction_hash' => $tx['hash'] ?? '',
                                'from_address' => $tx['from'] ?? '',
                                'to_address' => $tx['to'] ?? '',
                                'amount' => $amount,
                                'block_number' => isset($tx['blockNumber']) ? (int)$tx['blockNumber'] : null,
                                'transaction_at' => isset($tx['timeStamp']) ? (int)$tx['timeStamp'] : null,
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

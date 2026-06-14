<?php

namespace App\Services\Exchanges\Asset\Mexc;

use App\Enums\SpotStatusEnum;
use App\Exceptions\Exchange\CantResolveCoinexException;
use App\Exceptions\Exchange\CoinexHasProblemException;
use App\Models\Currency;
use App\Services\Exchanges\Asset\Contract\AssetInterface;
use App\Services\Exchanges\Asset\DTO\BalanceResponseDTO;
use App\Services\Exchanges\Asset\DTO\BuyDTORequest;
use App\Services\Exchanges\Asset\DTO\BuyDTOResponse;
use App\Services\Exchanges\Asset\DTO\WithdrawRequestDTO;
use App\Services\Exchanges\Asset\DTO\WithdrawResponseDTO;
use App\Services\Exchanges\Asset\Enum\WithdrawMethodEnum;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class AssetMexc implements AssetInterface
{

    public function getBalance(): array
    {
        // MEXC balance endpoint is /api/v3/account
        $response = MexcRequest::sendRequest('GET', '/api/v3/account');
        $json = $response->json();

        if (!isset($json['balances']) || !is_array($json['balances'])) {
            return [];
        }
        return array_map(function ($item) {
            return resolve(BalanceResponseDTO::class)
                ->setCcy($this->convertFromMexcCurrency($item['asset'] ?? ''))
                ->setFrozen($item['locked'] ?? '0')
                ->setAvailable($item['free'] ?? '0');
        }, $json['balances']);
    }

    public function placeOrder(BuyDTORequest $request): BuyDTOResponse
    {
        try {
            // Convert USDT to USDC for MEXC API calls
            $mexcSymbol = $this->convertToMexcSymbol($request->getMarket());

            $orderType = $request->getOrderType();

            // For USDC markets, force LIMIT orders and get price from depth
            $isUsdcMarket = str_ends_with($mexcSymbol, 'USDC');
            $price = null;

            if ($isUsdcMarket) {
                // Force LIMIT order for USDC markets
                $orderType = 'LIMIT';

                // Get the best price from order book depth
                $price = $this->getBestPriceFromDepth($mexcSymbol, $request->getSide());
                if (!$price) {
                    Log::channel('ref-exchange')->error('Failed to get price from depth for USDC market', [
                        'symbol' => $mexcSymbol,
                        'side' => $request->getSide()
                    ]);

                    return resolve(BuyDTOResponse::class)
                        ->setSpotStatus(SpotStatusEnum::BuyOrderFailed)
                        ->setErrorCode(0)
                        ->setErrorMessage('Unable to get market price for USDC pair')
                        ->setIsDone(false);
                }

                Log::channel('ref-exchange')->info('Using LIMIT order for USDC market', [
                    'symbol' => $mexcSymbol,
                    'side' => $request->getSide(),
                    'price' => $price
                ]);
            }

            $params = [
                'symbol' => $mexcSymbol,
                'side' => strtoupper($request->getSide()), // BUY or SELL
                'type' => strtoupper($orderType), // LIMIT or MARKET
                'quantity' => $request->getQuantity(),
            ];

            // Add price parameter for LIMIT orders
            if ($orderType === 'LIMIT' && $price) {
                $params['price'] = $price;
            }

            // MEXC expects all params as string
            foreach ($params as $k => $v) {
                if (is_numeric($v)) $params[$k] = (string)$v;
            }

            // Get currency precision
            $currency = Currency::where('symbol', $request->getCurrency())->first();
            $pricePrecision = $currency ? $currency->price_precision : null;
            $amountPrecision = $currency ? $currency->amount_precision : null;

            // Get USDT precision from database
            $usdtCurrency = Currency::where('symbol', 'USDT')->first();
            $quotePrecision = $usdtCurrency ? $usdtCurrency->amount_precision : 8;

            // Pass precision to MexcRequest
            $params['price_precision'] = $pricePrecision;
            $params['amount_precision'] = $amountPrecision;
            $params['quote_precision'] = $quotePrecision;

            $response = MexcRequest::sendRequest('POST', '/api/v3/order', $params);

        } catch (\Throwable $exception) {
            return resolve(BuyDTOResponse::class)
                ->setSpotStatus(SpotStatusEnum::ConnectionLosses)
                ->setErrorCode(0)
                ->setIsDone(false);
        }
        $json = $response->json();

        if (!$response->ok() || isset($json['code']) && $json['code'] !== 0) {
            $errorCode = $json['code'] ?? 0;
            $errorMsg = $json['msg'] ?? ($json['message'] ?? $response->body());

            Log::channel('ref-exchange')->error($response->json());

            return resolve(BuyDTOResponse::class)
                ->setSpotStatus(SpotStatusEnum::BuyOrderFailed)
                ->setErrorCode($errorCode)
                ->setErrorMessage($errorMsg)
                ->setIsDone(false);
        }

        // Success
        $filledAmount = $json['origQty'] ?? '0';
        $price = $json['price'] ?? '0';
        $filledValue = bcmul($filledAmount, $price, 8);

        return resolve(BuyDTOResponse::class)
            ->setIsDone(true)
            ->setErrorCode(0)
            ->setSpotStatus(SpotStatusEnum::BuyOrderSubmitted)
            ->setOrderId($json['orderId'] ?? null)
            ->setMarket($json['symbol'] ?? null)
            ->setCurrencySymbol($request->getCurrency())
            ->setSide($json['side'] ?? null)
            ->setAmount($json['origQty'] ?? null)
            ->setPrice($price)
            ->setDiscountFee('0')
            ->setFilledAmount($filledAmount)
            ->setFilledValue($filledValue)
            ->setCreatedAt(isset($json['transactTime']) ? Carbon::createFromTimestampMs($json['transactTime']) : Carbon::now())
            ->setResponseBody($response->body());
    }

    /**
     * Get the best price for USDC markets from order book depth
     */
    private function getBestPriceFromDepth(string $symbol, string $side): ?string
    {
        try {
            $response = Http::get('https://api.mexc.com/api/v3/depth', [
                'symbol' => $symbol,
                'limit' => 2
            ]);

            if (!$response->ok()) {
                Log::channel('ref-exchange')->warning('Failed to get depth data for symbol: ' . $symbol, [
                    'response' => $response->body()
                ]);
                return null;
            }

            $data = $response->json();

            // For sell orders, use the highest bid price (buyers) to ensure quick filling
            // For buy orders, use the lowest ask price (sellers) to ensure quick filling
            if (strtolower($side) === 'sell' && isset($data['bids'][0][0])) {
                return $data['bids'][0][0];
            } elseif (isset($data['asks'][0][0])) {
                return $data['asks'][0][0];
            }

            return null;
        } catch (\Throwable $exception) {
            Log::channel('ref-exchange')->error('Error getting depth data for symbol: ' . $symbol, [
                'error' => $exception->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Convert market symbol from USDT to USDC for MEXC API
     */
    private function convertToMexcSymbol(string $market): string
    {
        if (str_ends_with($market, 'USDT')) {
            return str_replace('USDT', 'USDC', $market);
        }

        return $market;
    }

    /**
     * Convert currency symbol from MEXC back to our system format
     */
    private function convertFromMexcCurrency(string $currency): string
    {
        if ($currency === 'USDC') {
            return 'USDT';
        }

        return $currency;
    }


    public function withdraw(WithdrawRequestDTO $requestDTO): WithdrawResponseDTO
    {
        $params = [
            'coin' => $requestDTO->getCurrency(),
            'address' => $requestDTO->getAddress(),
            'amount' => (string)$requestDTO->getAmount(),
        ];
        // dd($params);
        if ($requestDTO->getChain()) {
            // Map the chain to MEXC-specific network name
            $params['network'] = $this->mapChainToMexcNetwork($requestDTO->getChain());
        }
        // No getMemo() in WithdrawRequestDTO, so skip memo
        try {
            // Use sendWithdrawal method for withdrawal API
            $response = MexcRequest::sendWithdrawal('POST', '/api/v3/capital/withdraw', $params);
        } catch (\Throwable $exception) {
            report($exception);
            throw new CantResolveCoinexException("Can't Resolve https://api.mexc.com");
        }
        $json = $response->json();
        if (!$response->ok() || !isset($json['id'])) {
            $errorCode = $json['code'] ?? 0;
            $errorMsg = $json['msg'] ?? ($json['message'] ?? $response->body());

            Log::channel('ref-exchange')->error($response->json());

            throw new CoinexHasProblemException($errorMsg);
        }

        $withdrawId = $json['id'];

        // Try to get withdrawal details with fee information
        $actualFee = '0';
        $currencyFee = $requestDTO->getCurrency();
        $status = 'apply'; // Default to MEXC's APPLY status

        // Wait a moment for the withdrawal to be recorded in history
        sleep(2);

        $withdrawalDetails = $this->getWithdrawalDetails($withdrawId, $requestDTO->getCurrency());

        if ($withdrawalDetails) {
            $actualFee = $withdrawalDetails['transactionFee'] ?? '0';
            $status = $this->mapMexcStatusToString($withdrawalDetails['status'] ?? 3); // Default to WAIT if status not found
            $createdAt = $withdrawalDetails['applyTime'];
        }

        return resolve(WithdrawResponseDTO::class)
            ->setWithdrawId($withdrawId)
            ->setCreatedAt($createdAt ?? now()->timestamp) // Use timestamp as int
            ->setCurrency($requestDTO->getCurrency()) // Keep original currency (USDT)
            ->setChain($requestDTO->getChain() ?? '')
            ->setAmount($requestDTO->getAmount())
            ->setActualAmount($requestDTO->getAmount())
            ->setWithdrawMethod(WithdrawMethodEnum::ON_CHAIN->value ?? '')
            ->setAddress($requestDTO->getAddress())
            ->setConfirmationCount(0)
            ->setExploreAddress('')
            ->setStatus($status)
            ->setFee($actualFee)
            ->setCurrencyFee($currencyFee);
    }

    /**
     * Get withdrawal details from MEXC withdrawal history
     *
     * @param string $withdrawId
     * @param string $coin
     * @return array|null
     */
    private function getWithdrawalDetails(string $withdrawId, string $coin): ?array
    {
        try {
            $params = [
                'coin' => $coin,
                'limit' => 10, // Get last 10 withdrawals to find our withdrawal
            ];

            $response = MexcRequest::sendRequest('GET', '/api/v3/capital/withdraw/history', $params);

            if (!$response->successful()) {
                Log::channel('ref-exchange')->warning('Failed to fetch MEXC withdrawal history', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return null;
            }

            $history = $response->json();

            if (!is_array($history)) {
                Log::channel('ref-exchange')->warning('Invalid MEXC withdrawal history response format');
                return null;
            }

            // Find the withdrawal by ID
            foreach ($history as $withdrawal) {
                if (isset($withdrawal['id']) && $withdrawal['id'] === $withdrawId) {
                    return $withdrawal;
                }
            }

            Log::channel('ref-exchange')->info('Withdrawal not found in history yet', [
                'withdraw_id' => $withdrawId,
                'coin' => $coin
            ]);
        } catch (\Throwable $e) {
            Log::channel('ref-exchange')->error('Error fetching MEXC withdrawal details', [
                'error' => $e->getMessage(),
                'withdraw_id' => $withdrawId
            ]);
        }

        return null;
    }

    /**
     * Map MEXC withdrawal status codes to string
     *
     * @param int $status
     * @return string
     */
    private function mapMexcStatusToString(int $status): string
    {
        $statusMap = [
            1 => 'apply',           // APPLY
            2 => 'auditing',        // AUDITING
            3 => 'wait',            // WAIT
            4 => 'processing',      // PROCESSING
            5 => 'wait_packaging',  // WAIT_PACKAGING
            6 => 'wait_confirm',    // WAIT_CONFIRM
            7 => 'success',         // SUCCESS
            8 => 'failed',          // FAILED
            9 => 'cancel',          // CANCEL
            10 => 'manual',         // MANUAL
        ];

        return $statusMap[$status] ?? 'unknown';
    }

    /**
     * Map standard chain names to MEXC-specific network names
     */
    private function mapChainToMexcNetwork(string $chain): string
    {
        $chainMapping = [
            'ERC20' => 'ETH',    // Ethereum network
            'BEP20' => 'BSC',    // Binance Smart Chain
            'TRC20' => 'TRX',    // Tron network
            'BTC' => 'BTC',      // Bitcoin network
            'ETH' => 'ETH',      // Ethereum native
            'BSC' => 'BSC',      // BSC native
            'TRX' => 'TRX',      // Tron native
            'DOGE' => 'DOGE',    // Dogecoin
            'SOL' => 'SOL',      // Solana
            'POLYGON' => 'POLYGON', // Polygon
            'ARBITRUM' => 'ARB',  // Arbitrum
            'LTC' => 'LTC',      // Litecoin
            'AVAX' => 'AVAX',    // Avalanche
            'FTM' => 'FTM',      // Fantom
            'ARB' => 'ARB',      // Arbitrum
            'OP' => 'OP',        // Optimism
            'BASE' => 'BASE',    // Base
            'LINEA' => 'LINEA',  // Linea
            'SONIC' => 'SONIC',  // Sonic
        ];

        return $chainMapping[$chain] ?? $chain;
    }
}

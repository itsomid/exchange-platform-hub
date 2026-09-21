<?php

namespace App\Services\Exchanges\Asset\Mexc;

use App\Enums\SpotStatusEnum;
use App\Repositories\CurrencyRepository;
use App\Services\Exchanges\Asset\Contract\AssetInterface;
use App\Services\Exchanges\Asset\DTO\BalanceResponseDTO;
use App\Services\Exchanges\Asset\DTO\BuyDTORequest;
use App\Services\Exchanges\Asset\DTO\BuyDTOResponse;
use Carbon\Carbon;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class AssetMexc implements AssetInterface
{
   

    public function getBalance(): array
    {
        // MEXC balance endpoint is /api/v3/account
        $response = MexcRequest::send('GET', '/api/v3/account');
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
            // Ensure MX Deduct is enabled before placing order
            // $this->ensureMxDeductEnabled();
            
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
                $price = $this->getBestPriceFromDepth($mexcSymbol);
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
                'side' => $request->getSide(), // BUY or SELL
                'type' => $orderType, // LIMIT or MARKET (forced to LIMIT for USDC)
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
            $currency = (new CurrencyRepository())->getOne($request->getCurrency());
            $pricePrecision = $currency ? $currency->price_precision : null;
            $amountPrecision = $currency ? $currency->amount_precision : null;
            
            // Get USDT precision from database
            $usdtCurrency = (new CurrencyRepository())->getOne('USDT');
            $quotePrecision = $usdtCurrency ? $usdtCurrency->amount_precision : 8;

            // Pass precision to MexcRequest
            $params['price_precision'] = $pricePrecision;
            $params['amount_precision'] = $amountPrecision;
            $params['quote_precision'] = $quotePrecision;
            $response = MexcRequest::send('POST', '/api/v3/order', $params);
         
        } catch (\Throwable $exception) {
            return resolve(BuyDTOResponse::class)
                ->setSpotStatus(SpotStatusEnum::ConnectionLosses)
                ->setErrorCode(0)
                ->setIsDone(false);
        }
        $json = $response->json();

        if (!$response->ok() || isset($json['code']) && $json['code'] !== 0) {
            // MEXC error handling
   
            $errorCode = $json['code'] ?? 0;
            $errorMsg = $json['msg'] ?? ($json['message'] ?? $response->body());
     
            Log::channel('ref-exchange')->error( $response->json());
            // AdminNotification::logError($request->getMarket(), $request->getQuantity(), $response->body());
          
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
            ->setCurrencySymbol($currency->symbol)
            ->setSide($json['side'] ?? null)
            ->setAmount($json['origQty'] ?? null)
            ->setPrice($price)
            ->setDiscountFee(0)
            ->setFilledAmount($filledAmount)
            ->setFilledValue($filledValue)
            ->setCreatedAt(isset($json['transactTime']) ? \Carbon\Carbon::createFromTimestampMs($json['transactTime']) : null)
            ->setResponseBody($response->body());
    }
         /**
      * Get the best price for USDC markets from order book depth
      * Since side is always BUY, use the best ask price to ensure quick filling
      */
     private function getBestPriceFromDepth(string $symbol): ?string
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

             // For buy orders, use the lowest ask price (sellers) to ensure quick filling
             if (isset($data['asks'][0][0])) {
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
     * MEXC supports USDC pairs instead of USDT
     */
    private function convertToMexcSymbol(string $market): string
    {
        // Convert pairs like BTCUSDT to BTCUSDC
        if (str_ends_with($market, 'USDT')) {
            return str_replace('USDT', 'USDC', $market);
        }
        
        return $market;
    }

    /**
     * Convert currency symbol from MEXC back to our system format
     * Convert USDC back to USDT for our system
     */
    private function convertFromMexcCurrency(string $currency): string
    {
        // Convert USDC back to USDT
        if ($currency === 'USDC') {
            return 'USDT';
        }
        
        return $currency;
    }

     /**
     * Check if MX Deduct is enabled for spot commission fee
     */
    private function checkMxDeductStatus(): bool
    {
        try {
            $response = MexcRequest::sendRequest('GET', '/api/v3/mxDeduct/enable');
            $json = $response->json();
           
            if (!$response->ok()) {
                Log::channel('ref-exchange')->warning('Failed to check MX Deduct status', [
                    'response' => $response->body()
                ]);
                return false;
            }
            
            return $json['mxDeductEnable'] ?? false;
        } catch (\Throwable $exception) {
            Log::channel('ref-exchange')->error('Error checking MX Deduct status', [
                'error' => $exception->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Enable MX Deduct for spot commission fee
     */
    private function enableMxDeduct(): bool
    {
        try {
            $response = MexcRequest::sendRequest('POST', '/api/v3/mxDeduct/enable', [
                'mxDeductEnable' => 'true'
            ]);
            
            if (!$response->ok()) {
                Log::channel('ref-exchange')->warning('Failed to enable MX Deduct', [
                    'response' => $response->body()
                ]);
                return false;
            }
            
            Log::channel('ref-exchange')->info('MX Deduct enabled successfully');
            return true;
        } catch (\Throwable $exception) {
            Log::channel('ref-exchange')->error('Error enabling MX Deduct', [
                'error' => $exception->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Ensure MX Deduct is enabled before placing order
     */
    private function ensureMxDeductEnabled(): void
    {
        if (!$this->checkMxDeductStatus()) {
            Log::channel('ref-exchange')->info('MX Deduct is disabled, attempting to enable it');
            $this->enableMxDeduct();
        }else{
            Log::channel('ref-exchange')->info('MX Deduct is enabled');
        }
    }
}

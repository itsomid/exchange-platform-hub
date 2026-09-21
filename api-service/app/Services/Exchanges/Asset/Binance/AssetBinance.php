<?php

namespace App\Services\Exchanges\Asset\Binance;

use App\Enums\SpotStatusEnum;
use App\Repositories\CurrencyRepository;
use App\Services\Exchanges\Asset\Contract\AssetInterface;
use App\Services\Exchanges\Asset\DTO\BalanceResponseDTO;
use App\Services\Exchanges\Asset\DTO\BuyDTORequest;
use App\Services\Exchanges\Asset\DTO\BuyDTOResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AssetBinance implements AssetInterface
{
    public function getBalance(): array
    {
        $response = BinanceRequest::sendRequest('GET', '/api/v3/account');
        $json = $response->json();

        if (!$response->ok() || !isset($json['balances']) || !is_array($json['balances'])) {
            return [];
        }

        $balances = array_filter($json['balances'], function ($item) {
            $free = (float) ($item['free'] ?? 0);
            $locked = (float) ($item['locked'] ?? 0);

            return $free > 0 || $locked > 0;
        });

        return array_map(function ($item) {
            return resolve(BalanceResponseDTO::class)
                ->setCcy($item['asset'] ?? '')
                ->setFrozen($item['locked'] ?? '0')
                ->setAvailable($item['free'] ?? '0');
        }, array_values($balances));
    }

    public function placeOrder(BuyDTORequest $request): BuyDTOResponse
    {
        try {
            $symbol = $request->getMarket();
            $orderType = strtoupper($request->getOrderType());
            $side = strtoupper($request->getSide());

            $currency = (new CurrencyRepository())->getOne($request->getCurrency());
            $amountPrecision = $currency?->amount_precision;

            $params = [
                'symbol' => $symbol,
                'side' => $side,
                'type' => $orderType,
                'quantity' => formatNumberTrimZeros($request->getQuantity(), $amountPrecision),
            ];

            if ($orderType === 'LIMIT') {
                $price = $request->getPrice();
                if (empty($price)) {
                    return resolve(BuyDTOResponse::class)
                        ->setSpotStatus(SpotStatusEnum::BuyOrderFailed)
                        ->setErrorCode(0)
                        ->setErrorMessage('Price is required for LIMIT orders')
                        ->setIsDone(false);
                }

                $params['price'] = (string) $price;
                $params['timeInForce'] = 'GTC';
            }

            $response = BinanceRequest::sendRequest('POST', '/api/v3/order', $params);
        } catch (\Throwable $exception) {
            report($exception);

            return resolve(BuyDTOResponse::class)
                ->setSpotStatus(SpotStatusEnum::ConnectionLosses)
                ->setErrorCode(0)
                ->setIsDone(false);
        }

        $json = $response->json();

        if (!$response->ok() || (isset($json['code']) && $json['code'] !== 0)) {
            $errorCode = $json['code'] ?? 0;
            $errorMsg = $json['msg'] ?? ($json['message'] ?? $response->body());

            Log::channel('ref-exchange')->error('Binance placeOrder failed', [
                'response' => $json,
            ]);

            return resolve(BuyDTOResponse::class)
                ->setSpotStatus(SpotStatusEnum::BuyOrderFailed)
                ->setErrorCode((int) $errorCode)
                ->setErrorMessage($errorMsg)
                ->setIsDone(false);
        }

        $filledAmount = $json['executedQty'] ?? ($json['origQty'] ?? '0');
        $filledValue = $json['cummulativeQuoteQty'] ?? '0';
        $price = $json['price'] ?? '0';

        if ((float) $price <= 0 && (float) $filledAmount > 0 && (float) $filledValue > 0) {
            $price = bcdiv((string) $filledValue, (string) $filledAmount, 8);
        }

        $commission = '0';
        if (!empty($json['fills']) && is_array($json['fills'])) {
            foreach ($json['fills'] as $fill) {
                $commission = bcadd($commission, (string) ($fill['commission'] ?? '0'), 8);
            }
        }

        return resolve(BuyDTOResponse::class)
            ->setIsDone(true)
            ->setErrorCode(0)
            ->setSpotStatus(SpotStatusEnum::BuyOrderSubmitted)
            ->setOrderId($json['orderId'] ?? null)
            ->setMarket($json['symbol'] ?? $request->getMarket())
            ->setCurrencySymbol($request->getCurrency())
            ->setSide($json['side'] ?? $side)
            ->setAmount($json['origQty'] ?? $request->getQuantity())
            ->setPrice($price)
            ->setDiscountFee($commission)
            ->setFilledAmount($filledAmount)
            ->setFilledValue($filledValue)
            ->setCreatedAt(
                isset($json['transactTime'])
                    ? Carbon::createFromTimestampMs($json['transactTime'])
                    : Carbon::now()
            )
            ->setResponseBody($response->body());
    }
}

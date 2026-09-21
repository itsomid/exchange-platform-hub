<?php

namespace App\Services\Exchanges\Asset\Coinex;

use App\Enums\SpotStatusEnum;
use App\Services\Exchanges\AdminNotification;
use App\Services\Exchanges\Asset\Coinex\Authentication\MethodEnum;
use App\Services\Exchanges\Asset\Contract\AssetInterface;
use App\Services\Exchanges\Asset\DTO\BalanceResponseDTO;
use App\Services\Exchanges\Asset\DTO\BuyDTORequest;
use App\Services\Exchanges\Asset\DTO\BuyDTOResponse;
use Carbon\Carbon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Log;
use Throwable;

class AssetCoinex implements AssetInterface
{
    public function getBalance(): array
    {
        $response = CoinexRequest::send(MethodEnum::GET, '/v2/assets/spot/balance');

        return array_map(function ($item) {
            return resolve(BalanceResponseDTO::class)
                ->setCcy($item['ccy'])
                ->setFrozen($item['frozen'])
                ->setAvailable($item['available']);
        }, $response->json('data'));
    }

    public function placeOrder(BuyDTORequest $request): BuyDTOResponse
    {
        try {
            $response = CoinexRequest::send(MethodEnum::POST, '/v2/spot/order', [
                'market' => $request->getMarket(),
                'market_type' => $request->getMarketType(),
                'side' => $request->getSide(),
                'type' => $request->getOrderType(),
                'amount' => $request->getQuantity(),
                'ccy' => $request->getCurrency(),
            ]);
        } catch (ConnectionException | Throwable $exception) {
            report($exception);

            return resolve(BuyDTOResponse::class)
                ->setSpotStatus(SpotStatusEnum::ConnectionLosses)
                ->setErrorCode(0)
                ->setErrorMessage('عدم ارتباط با صرافی مرجع، لطفا بعدا تلاش کنید.')
                ->setIsDone(false);
        }
        //Balance Not Enough
        if ($response->json('code') === 3109) {
            Log::channel('ref-exchange')->info('Coinex Balance Not Enough In USDT');
            // Notification will be sent by the caller (OTCService) with complete context

            return resolve(BuyDTOResponse::class)
                ->setSpotStatus(SpotStatusEnum::NotEnoughBalance)
                ->setErrorCode($response->json('code'))
                ->setErrorMessage(CoinexError::mapErrorToResponse(CoinexError::INSUFFICIENT_BALANCE))
                ->setIsDone(false);
        }
        if ($response->json('code') === 3127) {
            Log::channel('ref-exchange')->info('Coinex SPOT is too small');
            AdminNotification::sendSpotTradingIsTooSmall($request->getMarket(), $request->getQuantity());

            return resolve(BuyDTOResponse::class)
                ->setSpotStatus(SpotStatusEnum::AmountTooSmall)
                ->setErrorCode($response->json('code'))
                ->setErrorMessage(CoinexError::mapErrorToResponse(CoinexError::BELOW_MIN_ORDER))
                ->setIsDone(false);
        }
        if ($response->json('code') === 3606) {
            Log::channel('ref-exchange')->info('Order price and the latest price deviation is too large');
            AdminNotification::sendPriceDifferenceTooLarge($request->getMarket(), $request->getQuantity(), $response->json('message'));

            return resolve(BuyDTOResponse::class)
                ->setSpotStatus(SpotStatusEnum::PriceDifferenceTooLarge)
                ->setErrorCode($response->json('code'))
                ->setErrorMessage(CoinexError::mapErrorToResponse(CoinexError::PRICE_DIFFERENCE_TOO_LARGE, $response->json('message')))
                ->setIsDone(false);
        }
        if (! $response->ok() || $response->json('code') !== 0) {
            Log::channel('ref-exchange')->info($response->body());
            AdminNotification::logError(
                $request->getMarket(),
                $request->getQuantity(),
                $response->body(),
                $request->getTradeType(),
                $request->getUserId(),
                $request->getOrderId(),
            );

            return resolve(BuyDTOResponse::class)
                ->setSpotStatus(SpotStatusEnum::BuyOrderFailed)
                ->setErrorCode($response->json('code'))
                ->setErrorMessage(
                    CoinexError::mapErrorToResponse(
                        CoinexError::tryFrom(
                            $response->json('code')
                        ),
                        $response->json('message')
                    )
                )
                ->setIsDone(false);
        }
        $data = $response->json('data');

        return resolve(BuyDTOResponse::class)
            ->setIsDone(true)
            ->setErrorCode($response->json('code'))
            ->setSpotStatus(SpotStatusEnum::BuyOrderSubmitted)
            ->setDiscountFee($data['discount_fee'])
            ->setOrderId($data['order_id'])
            ->setMarket($data['market'])
            ->setCurrencySymbol($data['ccy'])
            ->setSide($data['side'])
            ->setAmount($data['amount'])
            ->setPrice($data['price'])
            ->setUnfilledAmount($data['unfilled_amount'])
            ->setFilledAmount($data['filled_amount'])
            ->setFilledValue($data['filled_value'])
            ->setBaseFee($data['base_fee'])
            ->setQuoteFee($data['quote_fee'])
            ->setMakerFeeRate($data['maker_fee_rate'])
            ->setTakerFeeRate($data['taker_fee_rate'])
            ->setLastFillAmount($data['last_fill_amount'])
            ->setLastFillPrice($data['last_fill_price'])
            ->setCreatedAt(Carbon::createFromTimestampMs($data['created_at']))
            ->setResponseBody($response->body());
    }
}

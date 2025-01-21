<?php

namespace App\Services\Exchanges\Asset\Coinex;

use App\Services\Exchanges\Asset\Coinex\Authentication\MethodEnum;
use App\Services\Exchanges\Asset\Contract\AssetInterface;
use App\Services\Exchanges\Asset\DTO\BalanceResponseDTO;
use App\Services\Exchanges\Asset\DTO\BuyDTORequest;
use App\Services\Exchanges\Asset\DTO\BuyDTOResponse;
use Carbon\Carbon;
use Illuminate\Http\Client\ConnectionException;
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
        } catch (ConnectionException|Throwable $exception) {
            report($exception);

            return resolve(BuyDTOResponse::class)
                ->setIsDone(false);
        }
        //Balance Not Enough
        if ($response->json('code') === 3109) {
            report('Coinex Balance Not Enough In USDT');
        }
        if (! $response->ok() || $response->json('code') !== 0) {
            report($response->body());

            return resolve(BuyDTOResponse::class)
                ->setIsDone(false);
        }
        $data = $response->json('data');

        return resolve(BuyDTOResponse::class)
            ->setIsDone(true)
            ->setDiscountFee($data['discount_fee'])
            ->setOrderId($data['order_id'])
            ->setMarket($data['market'])
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

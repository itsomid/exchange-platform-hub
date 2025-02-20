<?php

namespace App\Services\Exchanges\Asset\Coinex;

use App\Enums\SpotStatusEnum;
use App\Exceptions\Exchange\CantResolveCoinexException;
use App\Exceptions\Exchange\CoinexHasProblemException;
use App\Services\Exchanges\AdminNotification;
use App\Services\Exchanges\Asset\Coinex\Authentication\MethodEnum;
use App\Services\Exchanges\Asset\Contract\AssetInterface;
use App\Services\Exchanges\Asset\DTO\BalanceResponseDTO;
use App\Services\Exchanges\Asset\DTO\BuyDTORequest;
use App\Services\Exchanges\Asset\DTO\BuyDTOResponse;
use App\Services\Exchanges\Asset\DTO\WithdrawRequestDTO;
use App\Services\Exchanges\Asset\DTO\WithdrawResponseDTO;
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
        } catch (ConnectionException|Throwable $exception) {
            report($exception);

            return resolve(BuyDTOResponse::class)
                ->setSpotStatus(SpotStatusEnum::ConnectionLosses)
                ->setErrorCode(0)
                ->setIsDone(false);
        }
        //Balance Not Enough
        if ($response->json('code') === 3109) {
            Log::channel('ref-exchange')->info('Coinex Balance Not Enough In USDT');
            AdminNotification::sendEnoughBalance($request->getMarket(), $request->getQuantity());

            return resolve(BuyDTOResponse::class)
                ->setSpotStatus(SpotStatusEnum::NotEnoughBalance)
                ->setErrorCode($response->json('code'))
                ->setIsDone(false);
        }
        if ($response->json('code') === 3127) {
            Log::channel('ref-exchange')->info('Coinex SPOT is too small');
            AdminNotification::sendSpotTradingIsTooSmall($request->getMarket(), $request->getQuantity());

            return resolve(BuyDTOResponse::class)
                ->setSpotStatus(SpotStatusEnum::AmountTooSmall)
                ->setErrorCode($response->json('code'))
                ->setIsDone(false);
        }
        if (! $response->ok() || $response->json('code') !== 0) {
            Log::channel('ref-exchange')->info($response->body());
            AdminNotification::logError($request->getMarket(), $request->getQuantity(), $response->body());

            return resolve(BuyDTOResponse::class)
                ->setSpotStatus(SpotStatusEnum::BuyOrderFailed)
                ->setErrorCode($response->json('code'))
                ->setErrorMessage(
                    CoinexError::mapErrorToResponse(
                        CoinexError::tryFrom(
                            $response->json('code')
                        )
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

    public function withdraw(WithdrawRequestDTO $requestDTO): WithdrawResponseDTO
    {
        $requestBody = [
            'ccy' => $requestDTO->getCurrency(),
            'to_address' => $requestDTO->getAddress(),
            'withdraw_method' => $requestDTO->getWithdrawMethod()->value,
            'amount' => $requestDTO->getAmount(),
            'fee_ccy' => 'CET',
        ];
        if ($requestDTO->getChain()) {
            $requestBody['chain'] = $requestDTO->getChain();
        }
        try {
            $response = CoinexRequest::send(MethodEnum::POST, '/v2/assets/withdraw', $requestBody);
        } catch (ConnectionException|Throwable $exception) {
            report($exception);
            throw new CantResolveCoinexException("Can't Resolve https://api.coinex.com");
        }

        //Balance Not Enough
        if ($response->json('code') === 3109) {
            Log::channel('ref-exchange')->warning('Coinex Balance Not Enough In USDT');
            AdminNotification::sendEnoughBalance('USDT', $requestDTO->getAmount());
        }
        if (! $response->successful() || $response->json('code') !== 0) {
            Log::channel('ref-exchange')->warning($response->body());
            throw new CoinexHasProblemException;
        }
        Log::channel('ref-exchange')->info($response->json('data'));
        $data = $response->json('data');

        return resolve(WithdrawResponseDTO::class)
            ->setWithdrawId($data['withdraw_id'])
            ->setExchange('coinex')
            ->setCreatedAt($data['created_at'])
            ->setCurrency($data['ccy'])
            ->setChain($data['chain'])
            ->setAmount($data['amount'])
            ->setActualAmount($data['actual_amount'])
            ->setWithdrawMethod($data['withdraw_method'])
            ->setAddress($data['to_address'])
            ->setConfirmationCount($data['confirmations'])
            ->setExploreAddress($data['explorer_address_url'])
            ->setStatus($data['status'])
            ->setFee($data['tx_fee'] > 0 ? $data['tx_fee'] : $data['fee_amount'])
            ->setCurrencyFee($data['fee_ccy']);
    }
}

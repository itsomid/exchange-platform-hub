<?php

namespace App\Services\Exchanges\Asset\Coinex;

use App\Enums\SpotStatusEnum;
use App\Exceptions\Exchange\CantResolveCoinexException;
use App\Exceptions\Exchange\CoinexWithdrawalException;
use App\Models\Currency;
use App\Services\Exchanges\Asset\AdminNotification;
use App\Services\Exchanges\Asset\Coinex\Authentication\MethodEnum;
use App\Services\Exchanges\Asset\Contract\AssetInterface;
use App\Services\Exchanges\Asset\DTO\BalanceResponseDTO;
use App\Services\Exchanges\Asset\DTO\BuyDTORequest;
use App\Services\Exchanges\Asset\DTO\BuyDTOResponse;
use App\Services\Exchanges\Asset\DTO\WithdrawRequestDTO;
use App\Services\Exchanges\Asset\DTO\WithdrawResponseDTO;
use App\Services\Exchanges\Asset\Coinex\Enum\CoinexWithdrawalError;
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
        
        // Balance Not Enough
        if ($response->json('code') === 3109) {
            Log::channel('ref-exchange')->info('Coinex Balance Not Enough');

            return resolve(BuyDTOResponse::class)
                ->setSpotStatus(SpotStatusEnum::NotEnoughBalance)
                ->setErrorCode($response->json('code'))
                ->setIsDone(false);
        }
        
        if ($response->json('code') === 3127) {
            Log::channel('ref-exchange')->info('Coinex SPOT is too small');

            return resolve(BuyDTOResponse::class)
                ->setSpotStatus(SpotStatusEnum::AmountTooSmall)
                ->setErrorCode($response->json('code'))
                ->setIsDone(false);
        }
        
        if ($response->json('code') === 3606) {
            Log::channel('ref-exchange')->info('Order price and the latest price deviation is too large');

            return resolve(BuyDTOResponse::class)
                ->setSpotStatus(SpotStatusEnum::PriceDifferenceTooLarge)
                ->setErrorCode($response->json('code'))
                ->setIsDone(false);
        }
        
        if (!$response->ok() || $response->json('code') !== 0) {
            Log::channel('ref-exchange')->info($response->body());

            return resolve(BuyDTOResponse::class)
                ->setSpotStatus(SpotStatusEnum::BuyOrderFailed)
                ->setErrorCode($response->json('code'))
                ->setErrorMessage(
                    CoinexError::mapErrorToResponse(
                        CoinexError::tryFrom($response->json('code'))
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
            $requestBody['chain'] = $this->mapChainToCoinexNetwork($requestDTO->getChain());
        }
        try {
            $response = CoinexRequest::send(MethodEnum::POST, '/v2/assets/withdraw', $requestBody);
        } catch (ConnectionException | Throwable $exception) {
            report($exception);
            throw new CantResolveCoinexException($exception->getMessage(), (int) $exception->getCode(), $exception);
        }

        if ($response->json('code') !== 0) {
            Log::channel('ref-exchange')->error('Coinex withdrawal failed with code: ' . $response->json('code') . ', message: ' . $response->json('message') . ', response: ' . $response->body());

            $errorCode = $response->json('code');
            $mappedError = CoinexWithdrawalError::tryFrom($errorCode);
            $errorResponse = CoinexWithdrawalError::mapErrorToResponse($mappedError);

            AdminNotification::dispatchCoinexHasProblem(
                $errorResponse,
                $requestDTO->getCurrency(),
                $requestDTO->getAmount()
            );

            throw new CoinexWithdrawalException(
                "Coinex withdrawal failed with code: {$response->json('code')}, message: {$response->json('message')}"
            );
        }

        $data = $response->json('data');

        return resolve(WithdrawResponseDTO::class)
            ->setWithdrawId($data['withdraw_id'])
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

    /**
     * CoinEx chain names that differ from CurrencyChainEnum.
     * AVAX C-Chain is AVA_C; X-Chain is AVA and must not be used for EVM HD wallets.
     */
    private function mapChainToCoinexNetwork(string $chain): string
    {
        $chainMapping = [
            'AVALANCHE' => 'AVA_C',
            'POLYGON' => 'MATIC',
        ];

        return $chainMapping[$chain] ?? $chain;
    }
}

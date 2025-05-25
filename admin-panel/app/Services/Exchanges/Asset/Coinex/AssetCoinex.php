<?php

namespace App\Services\Exchanges\Asset\Coinex;

use App\Exceptions\Exchange\CantResolveCoinexException;
use App\Exceptions\Exchange\CoinexWithdrawalException;
use App\Models\Currency;
use App\Services\Exchanges\Asset\AdminNotification;
use App\Services\Exchanges\Asset\Coinex\Authentication\MethodEnum;
use App\Services\Exchanges\Asset\Contract\AssetInterface;
use App\Services\Exchanges\Asset\DTO\BalanceResponseDTO;
use App\Services\Exchanges\Asset\DTO\WithdrawRequestDTO;
use App\Services\Exchanges\Asset\DTO\WithdrawResponseDTO;
use App\Services\Exchanges\Enums\CoinexWithdrawalError;
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

    public function withdraw(WithdrawRequestDTO $requestDTO): WithdrawResponseDTO
    {
        $formattedAmount = $this->formatWithdrawalAmount($requestDTO->getCurrency(), $requestDTO->getAmount());

        $requestBody = [
            'ccy' => $requestDTO->getCurrency(),
            'to_address' => $requestDTO->getAddress(),
            'withdraw_method' => $requestDTO->getWithdrawMethod()->value,
            'amount' => $formattedAmount,
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
     * Format the withdrawal amount according to the currency's precision
     *
     * @param string $currencySymbol
     * @param string $amount
     * @return string
     */
    private function formatWithdrawalAmount(string $currencySymbol, string $amount): string
    {

        $currency = Currency::where('symbol', $currencySymbol)->first();

        $precision = $currency ? $currency->precision : 8;

        return formatNumber((float)$amount, $precision, '');
    }
}

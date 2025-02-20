<?php

namespace App\Infrastructure\HDWallet;

use App\Exceptions\V1\Wallet\InternalWalletHasProblemException;
use App\Infrastructure\HDWallet\DTO\Withdrawal\GetWithdrawalStatusRequestDTO;
use App\Infrastructure\HDWallet\DTO\Withdrawal\GetWithdrawalStatusResponseDTO;
use App\Infrastructure\HDWallet\DTO\Withdrawal\WithdrawRequestDTO;
use App\Infrastructure\HDWallet\DTO\Withdrawal\WithdrawResponseDTO;
use App\Infrastructure\HDWallet\Exceptions\HDDWalletUnavailable;
use App\Infrastructure\HDWallet\Exceptions\NotFoundException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HDWalletWithdrawalService
{
    public function withdraw(WithdrawRequestDTO $requestDTO)
    {
        try {
            $requestBody = [
                'withdrawal_id' => (string) $requestDTO->getWithdrawalId(),
                'user_id' => $requestDTO->getUserId(),
                'cryptocurrency' => $requestDTO->getCurrencySymbol(),
                'blockchain' => $requestDTO->getBlockchain(),
                'received_amount' => $requestDTO->getAmount(),
                'withdrawal_address' => $requestDTO->getWithdrawAddress(),
                // 'memo' => $requestDTO->getWithdrawalId(),
                // 'remarks' => $requestDTO->getWithdrawalId(),
            ];

            $response = Http::post(HDWallet::getBaseUrl()."/api/v1/wallet/withdrawals?symbol={$requestDTO->getCurrencySymbol()}&blockchain={$requestDTO->getBlockchain()}", $requestBody);
        } catch (ConnectionException $exception) {
            report($exception);
            throw new HDDWalletUnavailable;
        }
        $data = $response->json();
        if (! $response->successful()) {
            $logMessage = [
                'request_body' => $requestBody,
                'response_body' => $response->body(),
            ];

            report(json_encode($logMessage));
            Log::channel('hd-wallet')->error('HD Wallet Request and Response:', $logMessage);
            throw new InternalWalletHasProblemException;
        }

        return resolve(WithdrawResponseDTO::class)
            ->setWithdrawalId($data['withdrawal_id'])
            ->setUserId($data['user_id'])
            ->setCurrencySymbol($data['cryptocurrency'])
            ->setBlockchain($data['blockchain'])
            ->setAmount($data['received_amount'])
            ->setWithdrawAddress($data['withdrawal_address'])
            ->setTransactionHash($data['transaction_hash'])
            ->setBlockNumber($data['blockNumber'])
            ->setStatus($data['status'])
            ->setTimestamp($data['timestamp'])
            ->setFee($data['fee'])
            ->setDescription($data['descriptions']);
    }

    public function getStatus(GetWithdrawalStatusRequestDTO $requestDTO): GetWithdrawalStatusResponseDTO
    {
        try {
            $response = Http::get(HDWallet::getBaseUrl()."/api/v1/wallet/withdrawals/{$requestDTO->getWithdrawalId()}?symbol={$requestDTO->getCurrencySymbol()}&blockchain={$requestDTO->getBlockchain()}");
        } catch (ConnectionException $exception) {
            report($exception);
            throw new HDDWalletUnavailable;
        }

        if ($response->notFound()) {
            throw new NotFoundException;
        }
        $data = $response->json();
        if (! $response->successful()) {
            report($response->body());
            Log::channel('hd-wallet')->error('HD Wallet Response Changed:'.$response->body());
            throw new InternalWalletHasProblemException;
        }

         Log::channel('hd-wallet')->info($response->body());

        return resolve(GetWithdrawalStatusResponseDTO::class)
            ->setWithdrawalId($data['withdrawal_id'])
            ->setUserId($data['user_id'])
            ->setCurrencySymbol($data['cryptocurrency'])
            ->setBlockchain($data['blockchain'])
            ->setAmount($data['received_amount'])
            ->setWithdrawAddress($data['withdrawal_address'])
            ->setTransactionHash($data['transaction_hash'])
            ->setBlockNumber($data['blockNumber'])
            ->setStatus($data['status'])
            ->setTimestamp($data['timestamp'])
            ->setFee($data['fee'])
            ->setDescription($data['descriptions']);
    }
}

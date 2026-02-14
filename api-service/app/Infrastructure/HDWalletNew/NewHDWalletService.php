<?php

namespace App\Infrastructure\HDWalletNew;

use App\Infrastructure\HDWalletNew\DTO\Address\GenerateAddressRequestDTO;
use App\Infrastructure\HDWalletNew\DTO\Address\GenerateAddressResponseDTO;
use App\Infrastructure\HDWalletNew\DTO\Deposit\GetDepositListsRequestDTO;
use App\Infrastructure\HDWalletNew\DTO\Deposit\GetDepositListsResponseDTO;
use App\Infrastructure\HDWalletNew\DTO\Withdrawal\GetWithdrawalStatusRequestDTO;
use App\Infrastructure\HDWalletNew\DTO\Withdrawal\GetWithdrawalStatusResponseDTO;
use App\Infrastructure\HDWalletNew\DTO\Withdrawal\WithdrawRequestDTO;
use App\Infrastructure\HDWalletNew\DTO\Withdrawal\WithdrawResponseDTO;
use App\Infrastructure\HDWalletNew\Exceptions\HDWalletNewServerError;
use App\Infrastructure\HDWalletNew\Exceptions\HDWalletNewUnavailable;
use App\Infrastructure\HDWalletNew\Exceptions\HDWalletNewNotFoundException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NewHDWalletService
{
    private function baseUrl(): string
    {
        return config('hd-wallet.new_base_url');
    }

    private function apiKey(): string
    {
        return config('hd-wallet.new_api_key');
    }

    private function httpClient()
    {
        return Http::withHeaders([
            'x-api-key' => $this->apiKey(),
            'Content-Type' => 'application/json',
        ]);
    }

    // ──────────────────────────────────────────────
    //  Address Generation
    // ──────────────────────────────────────────────

    /**
     * Generate or get existing address for a user
     * POST /api/addresses/generate
     *
     * @throws HDWalletNewUnavailable
     * @throws HDWalletNewServerError
     */
    public function generateAddress(GenerateAddressRequestDTO $requestDTO): GenerateAddressResponseDTO
    {
        try {
            $requestBody = [
                'userId' => (string) $requestDTO->getUserId(),
                'network' => $requestDTO->getNetwork(),
                'currencySymbol' => $requestDTO->getCurrencySymbol(),
            ];

            $response = $this->httpClient()
                ->post($this->baseUrl() . '/api/addresses/generate', $requestBody);

            if (! $response->successful()) {
                Log::channel('hd-wallet')->error('HD Wallet New - Generate Address Failed:', [
                    'user_id' => $requestDTO->getUserId(),
                    'network' => $requestDTO->getNetwork(),
                    'currency_symbol' => $requestDTO->getCurrencySymbol(),
                    'request_body' => $requestBody,
                    'response_status' => $response->status(),
                    'response_body' => $response->body(),
                ]);
                throw new HDWalletNewServerError($response->body());
            }

            $data = $response->json('data');

            return resolve(GenerateAddressResponseDTO::class)
                ->setUserId($data['userId'])
                ->setNetwork($data['network'])
                ->setCurrencySymbol($data['currencySymbol'])
                ->setAddress($data['address'])
                ->setAddressIndex($data['addressIndex'])
                ->setDerivationPath($data['derivationPath'])
                ->setIsActive($data['isActive'])
                ->setDepositCount($data['depositCount'] ?? 0)
                ->setTotalDeposited($data['totalDeposited'] ?? '0')
                ->setLastDepositAt($data['lastDepositAt'] ?? null)
                ->setCreatedAt($data['createdAt']);

        } catch (ConnectionException $exception) {
            Log::channel('hd-wallet')->error('HD Wallet New - Connection Failed:', [
                'user_id' => $requestDTO->getUserId(),
                'network' => $requestDTO->getNetwork(),
                'exception' => $exception->getMessage(),
            ]);
            throw new HDWalletNewUnavailable('HD Wallet New service is unavailable');
        }
    }

    // ──────────────────────────────────────────────
    //  Withdrawal
    // ──────────────────────────────────────────────

    /**
     * Create a new withdrawal request
     * POST /api/withdrawals
     *
     * @throws HDWalletNewUnavailable
     * @throws HDWalletNewServerError
     */
    public function withdraw(WithdrawRequestDTO $requestDTO): WithdrawResponseDTO
    {
        try {
            $requestBody = [
                'withdrawal_id' => (string) $requestDTO->getWithdrawalId(),
                'userId' => (string) $requestDTO->getUserId(),
                'network' => $requestDTO->getNetwork(),
                'currencySymbol' => $requestDTO->getCurrencySymbol(),
                'amount' => $requestDTO->getAmount(),
                'toAddress' => $requestDTO->getToAddress(),
                'priority' => $requestDTO->getPriority(),
            ];

            if ($requestDTO->getTag()) {
                $requestBody['tag'] = $requestDTO->getTag();
            }

            $response = $this->httpClient()
                ->post($this->baseUrl() . '/api/withdrawals', $requestBody);

            if (! $response->successful()) {
                Log::channel('hd-wallet')->error('HD Wallet New - Withdrawal Failed:', [
                    'withdrawal_id' => $requestDTO->getWithdrawalId(),
                    'request_body' => $requestBody,
                    'response_status' => $response->status(),
                    'response_body' => $response->body(),
                ]);
                throw new HDWalletNewServerError($response->body());
            }

            $data = $response->json('data');

            return resolve(WithdrawResponseDTO::class)
                ->setWithdrawalId($data['withdrawalId'])
                ->setStatus($data['status'])
                ->setRequestedAmount($data['requestedAmount'])
                ->setNetwork($data['network'])
                ->setCurrencySymbol($data['currencySymbol'])
                ->setToAddress($data['toAddress'])
                ->setCreatedAt($data['createdAt'])
                ->setEstimatedProcessingTime($data['estimatedProcessingTime'] ?? null);

        } catch (ConnectionException $exception) {
            Log::channel('hd-wallet')->error('HD Wallet New - Withdrawal Connection Failed:', [
                'withdrawal_id' => $requestDTO->getWithdrawalId(),
                'exception' => $exception->getMessage(),
            ]);
            throw new HDWalletNewUnavailable('HD Wallet New service is unavailable');
        }
    }

    /**
     * Get withdrawal status
     * GET /api/withdrawals/:withdrawalId
     *
     * @throws HDWalletNewUnavailable
     * @throws HDWalletNewServerError
     * @throws HDWalletNewNotFoundException
     */
    public function getWithdrawalStatus(GetWithdrawalStatusRequestDTO $requestDTO): GetWithdrawalStatusResponseDTO
    {
        try {
            $response = $this->httpClient()
                ->get($this->baseUrl() . '/api/withdrawals/' . $requestDTO->getWithdrawalId());

            if ($response->notFound()) {
                throw new HDWalletNewNotFoundException('Withdrawal not found');
            }

            if (! $response->successful()) {
                Log::channel('hd-wallet')->error('HD Wallet New - Get Withdrawal Status Failed:', [
                    'withdrawal_id' => $requestDTO->getWithdrawalId(),
                    'response_status' => $response->status(),
                    'response_body' => $response->body(),
                ]);
                throw new HDWalletNewServerError($response->body());
            }

            $data = $response->json('data');

            Log::channel('hd-wallet')->info('HD Wallet New - Withdrawal Status:', $data);

            $dto = resolve(GetWithdrawalStatusResponseDTO::class)
                ->setWithdrawalId($data['withdrawalId'])
                ->setUserId($data['userId'])
                ->setStatus($data['status'])
                ->setNetwork($data['network'])
                ->setCurrencySymbol($data['currencySymbol'])
                ->setRequestedAmount($data['requestedAmount'])
                ->setActualAmount($data['actualAmount'] ?? null)
                ->setFee($data['fee'] ?? null)
                ->setToAddress($data['toAddress'])
                ->setTxHash($data['txHash'] ?? null)
                ->setConfirmations($data['confirmations'] ?? null)
                ->setRequiredConfirmations($data['requiredConfirmations'] ?? null)
                ->setCreatedAt($data['createdAt'])
                ->setProcessedAt($data['processedAt'] ?? null)
                ->setConfirmedAt($data['confirmedAt'] ?? null);

            if (isset($data['failureReason'])) {
                $dto->setFailureReason($data['failureReason']);
                $dto->setFailedAt($data['failedAt'] ?? null);
            }

            return $dto;

        } catch (ConnectionException $exception) {
            Log::channel('hd-wallet')->error('HD Wallet New - Get Withdrawal Status Connection Failed:', [
                'withdrawal_id' => $requestDTO->getWithdrawalId(),
                'exception' => $exception->getMessage(),
            ]);
            throw new HDWalletNewUnavailable('HD Wallet New service is unavailable');
        }
    }

    // ──────────────────────────────────────────────
    //  Deposit
    // ──────────────────────────────────────────────

    /**
     * Get user deposits
     * GET /api/deposits/user/:userId
     *
     * @throws HDWalletNewUnavailable
     * @throws HDWalletNewServerError
     *
     * @return GetDepositListsResponseDTO[]
     */
    public function getDepositLists(GetDepositListsRequestDTO $requestDTO): array
    {
        try {
            $queryParams = [];
            if ($requestDTO->getNetwork()) {
                $queryParams['network'] = $requestDTO->getNetwork();
            }
            if ($requestDTO->getCurrencySymbol()) {
                $queryParams['currencySymbol'] = $requestDTO->getCurrencySymbol();
            }
            $queryParams['limit'] = $requestDTO->getLimit();
            $queryParams['liveScan'] = true;

            $url = $this->baseUrl() . '/api/deposits/user/' . $requestDTO->getUserId();

            $response = $this->httpClient()
                ->get($url, $queryParams);

            if (! $response->successful()) {
                Log::channel('hd-wallet')->error('HD Wallet New - Get Deposit Lists Failed:', [
                    'user_id' => $requestDTO->getUserId(),
                    'response_status' => $response->status(),
                    'response_body' => $response->body(),
                ]);
                throw new HDWalletNewServerError($response->body());
            }

            $data = $response->json('data');
            $deposits = $data['deposits'] ?? [];

            return array_map(function (array $item) {
                return resolve(GetDepositListsResponseDTO::class)
                    ->setDepositId($item['depositId'])
                    ->setTxHash($item['txHash'])
                    ->setNetwork($item['network'])
                    ->setCurrencySymbol($item['currency'])
                    ->setUserId($item['userId'] ?? null)
                    ->setAmount((string) $item['amount'])
                    ->setStatus($item['status'])
                    ->setConfirmations($item['confirmations'] ?? null)
                    ->setRequiredConfirmations($item['requiredConfirmations'] ?? null)
                    ->setToAddress($item['toAddress'])
                    ->setFromAddress($item['fromAddress'] ?? null)
                    ->setBlockNumber($item['blockNumber'] ?? null)
                    ->setContractAddress($item['contractAddress'] ?? null)
                    ->setIsCredited($item['isCredited'] ?? false)
                    ->setCreditedAt($item['creditedAt'] ?? null)
                    ->setDetectedAt($item['detectedAt'] ?? null)
                    ->setCreatedAt($item['createdAt']);
            }, $deposits);

        } catch (ConnectionException $exception) {
            Log::channel('hd-wallet')->error('HD Wallet New - Get Deposit Lists Connection Failed:', [
                'user_id' => $requestDTO->getUserId(),
                'exception' => $exception->getMessage(),
            ]);
            throw new HDWalletNewUnavailable('HD Wallet New service is unavailable');
        }
    }
}

<?php

namespace App\Infrastructure\HDWallet;

use App\Exceptions\V1\Wallet\InternalWalletHasProblemException;
use App\Infrastructure\HDWallet\DTO\HDDeposit\GetDepositListsRequestDTO;
use App\Infrastructure\HDWallet\DTO\HDDeposit\GetDepositListsResponseDTO;
use App\Infrastructure\HDWallet\DTO\Withdrawal\GetWithdrawalStatusRequestDTO;
use App\Infrastructure\HDWallet\DTO\Withdrawal\GetWithdrawalStatusResponseDTO;
use App\Infrastructure\HDWallet\DTO\Withdrawal\WithdrawRequestDTO;
use App\Infrastructure\HDWallet\DTO\Withdrawal\WithdrawResponseDTO;
use App\Infrastructure\HDWallet\Exceptions\HDDWalletServerError;
use App\Infrastructure\HDWallet\Exceptions\HDDWalletUnavailable;
use App\Infrastructure\HDWallet\Exceptions\NotFoundException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OldHDWalletService
{
    private function baseUrl(): string
    {
        return config('hd-wallet.base_url');
    }

    // ──────────────────────────────────────────────
    //  Address Generation
    // ──────────────────────────────────────────────

    /**
     * @throws HDDWalletUnavailable
     * @throws HDDWalletServerError
     */
    public function generateAddress(int $userId, string $blockchainName): string
    {
        try {
            $response = Http::post($this->baseUrl() . "/api/v1/wallet/{$blockchainName}", [
                'user_id' => $userId,
                'blockchain' => $blockchainName,
            ]);
        } catch (ConnectionException $exception) {
            report($exception);
            throw new HDDWalletUnavailable;
        }

        // Already exists
        if ($response->badRequest()) {
            $response = Http::get($this->baseUrl() . "/api/v1/wallet/{$blockchainName}/{$userId}");
        }

        if (! ($response->ok() || $response->created())) {
            report($response->body());
            throw new HDDWalletServerError;
        }

        return $response->json('address');
    }

    // ──────────────────────────────────────────────
    //  Withdrawal
    // ──────────────────────────────────────────────

    /**
     * @throws HDDWalletUnavailable
     * @throws InternalWalletHasProblemException
     */
    public function withdraw(WithdrawRequestDTO $requestDTO): WithdrawResponseDTO
    {
        try {
            $requestBody = [
                'withdrawal_id' => (string) $requestDTO->getWithdrawalId(),
                'user_id' => $requestDTO->getUserId(),
                'cryptocurrency' => $requestDTO->getCurrencySymbol(),
                'blockchain' => $requestDTO->getBlockchain(),
                'received_amount' => $requestDTO->getAmount(),
                'withdrawal_address' => $requestDTO->getWithdrawAddress(),
            ];

            $response = Http::post($this->baseUrl() . "/api/v1/wallet/withdrawals?symbol={$requestDTO->getCurrencySymbol()}&blockchain={$requestDTO->getBlockchain()}", $requestBody);

            $data = $response->json();
            if (! $response->successful()) {
                Log::channel('hd-wallet')->error("HD Wallet Request and Response - Withdrawal ID: {$requestDTO->getWithdrawalId()}:", [
                    'withdrawal_id' => $requestDTO->getWithdrawalId(),
                    'request_body' => $requestBody,
                    'response_body' => $response->body(),
                ]);
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
        } catch (ConnectionException $exception) {
            throw new HDDWalletUnavailable;
        }
    }

    /**
     * @throws HDDWalletUnavailable
     * @throws NotFoundException
     * @throws InternalWalletHasProblemException
     */
    public function getWithdrawalStatus(GetWithdrawalStatusRequestDTO $requestDTO): GetWithdrawalStatusResponseDTO
    {
        try {
            $response = Http::get($this->baseUrl() . "/api/v1/wallet/withdrawals/{$requestDTO->getWithdrawalId()}?symbol={$requestDTO->getCurrencySymbol()}&blockchain={$requestDTO->getBlockchain()}");
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
            Log::channel('hd-wallet')->error('HD Wallet Response Changed:' . $response->body());
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

    // ──────────────────────────────────────────────
    //  Deposit
    // ──────────────────────────────────────────────

    /**
     * @throws HDDWalletUnavailable
     * @throws InternalWalletHasProblemException
     *
     * @return GetDepositListsResponseDTO[]
     */
    public function getDepositLists(GetDepositListsRequestDTO $requestDTO): array
    {
        try {
            $network = $requestDTO->getBlockchain();
            $requestBody = [
                'network' => $network,
                'token_symbol' => $requestDTO->getCurrencySymbol(),
                'contract_address' => $requestDTO->getContractAddress(),
                'address' => $requestDTO->getWalletAddress(),
                'limit' => $requestDTO->getLimit() ?? 50,
            ];

            $response = Http::post($this->baseUrl() . '/api/v1/universal/deposit/transaction-history', $requestBody);
        } catch (ConnectionException $exception) {
            report($exception);
            throw new HDDWalletUnavailable;
        }

        if ($response->serverError()) {
            report($response->body());
            throw new InternalWalletHasProblemException;
        }

        $data = $response->json();
        if (! $response->ok()) {
            Log::channel('hd-wallet')->error('HD Wallet Response Changed:' . $response->body());
            throw new InternalWalletHasProblemException;
        }

        if (isset($data['error']) && $data['error'] !== null) {
            Log::channel('hd-wallet')->error('HD Wallet API Error: ' . $data['error']);
            throw new InternalWalletHasProblemException;
        }

        if (isset($data['status']) && $data['status'] !== 'success') {
            Log::channel('hd-wallet')->error('HD Wallet API Status Error: ' . ($data['status'] ?? 'unknown'));
            throw new InternalWalletHasProblemException;
        }

        $transactions = $data['transactions'] ?? [];

        return array_map(function (array $item) use ($requestDTO, $network) {
            return resolve(GetDepositListsResponseDTO::class)
                ->setTimestamp($item['timestamp'])
                ->setCryptocurrency($requestDTO->getCurrencySymbol())
                ->setAmount((string) $item['value'])
                ->setTransactionHash($item['hash'])
                ->setStatus($item['status'])
                ->setConfirmationBlocks($item['confirmations'] ?? null)
                ->setBlockChain(strtoupper($network))
                ->setWalletAddress($requestDTO->getWalletAddress())
                ->setContractAddress($item['contract_address'] ?? null)
                ->setType($item['type'] ?? null)
                ->setBlockNumber($item['block_number'] ?? null)
                ->setFrom($item['from'])
                ->setTo($requestDTO->getWalletAddress())
                ->setGasPrice($item['gas_price'] ?? null)
                ->setGasUsed($item['gas_used'] ?? null);
        }, $transactions);
    }
}

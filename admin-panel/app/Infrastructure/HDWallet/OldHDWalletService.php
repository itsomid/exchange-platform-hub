<?php

namespace App\Infrastructure\HDWallet;

use App\Exceptions\V1\Wallet\InternalWalletHasProblemException;
use App\Functions\FlashMessages\Toast;
use App\Infrastructure\HDWallet\DTO\HDDeposit\GetDepositListsRequestDTO;
use App\Infrastructure\HDWallet\DTO\HDDeposit\GetDepositListsResponseDTO;
use App\Infrastructure\HDWallet\DTO\Withdrawal\GetWithdrawalStatusRequestDTO;
use App\Infrastructure\HDWallet\DTO\Withdrawal\GetWithdrawalStatusResponseDTO;
use App\Infrastructure\HDWallet\DTO\Withdrawal\WithdrawRequestDTO;
use App\Infrastructure\HDWallet\DTO\Withdrawal\WithdrawResponseDTO;
use App\Infrastructure\HDWallet\Exceptions\HDDWalletUnavailable;
use App\Infrastructure\HDWallet\Exceptions\NotFoundException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * OldHDWalletService - Legacy HD Wallet Service
 * 
 * Consolidated service for old HD Wallet system (Python/Go based)
 * Combines deposit and withdrawal operations
 */
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
     * Generate or get existing address for a user
     * POST /api/v1/wallet/{blockchain}
     * 
     * @throws HDDWalletUnavailable
     * @throws InternalWalletHasProblemException
     */
    public function generateAddress(int $userId, string $blockchain): string
    {
        try {
            $response = Http::post($this->baseUrl() . "/api/v1/wallet/{$blockchain}", [
                'user_id' => $userId,
                'blockchain' => $blockchain,
            ]);
        } catch (ConnectionException $exception) {
            report($exception);
            throw new HDDWalletUnavailable;
        }

        // If address already exists in HDWallet, get it
        if ($response->badRequest()) {
            try {
                $response = Http::get($this->baseUrl() . "/api/v1/wallet/{$blockchain}/{$userId}");
            } catch (ConnectionException $exception) {
                report($exception);
                throw new HDDWalletUnavailable;
            }
        }

        if (!($response->ok() || $response->created())) {
            report($response->body());
            Log::channel('hd-wallet')->error('HD Wallet Generate Address Failed: ' . $response->body());
            throw new InternalWalletHasProblemException('HDWallet server error');
        }

        return $response->json('address');
    }

    // ──────────────────────────────────────────────
    //  Deposit Operations
    // ──────────────────────────────────────────────

    /**
     * Get deposit transaction history
     * 
     * @return GetDepositListsResponseDTO[]
     */
    public function getDepositLists(GetDepositListsRequestDTO $requestDTO): array
    {
        try {
            $network = $requestDTO->getNetwork();
            $contractAddress = $requestDTO->getContractAddress();
            $requestBody = [
                'network' => $network,
                'token_symbol' => $requestDTO->getTokenSymbol(),
                'contract_address' => $contractAddress,
                'address' => $requestDTO->getAddress(),
                'limit' => $requestDTO->getLimit() ?? 50
            ];

            $response = Http::timeout(60)->post($this->baseUrl() . '/api/v1/universal/deposit/transaction-history', $requestBody);
        } catch (ConnectionException $exception) {
            report($exception);
            Toast::message('سرویس کیف پول موقتاً در دسترس نیست. لطفاً بعداً تلاش کنید.')
                ->danger()
                ->notify();
            return [];
        }

        if ($response->serverError()) {
            report($response->body());
            Toast::message('خطا در سرویس کیف پول. لطفاً بعداً تلاش کنید.')
                ->danger()
                ->notify();
            return [];
        }

        $data = $response->json();
        if (! $response->ok()) {
            Log::channel('hd-wallet')->error('HD Wallet Response Changed:' . $response->body());
            Toast::message('خطا در سرویس کیف پول. لطفاً بعداً تلاش کنید.')
                ->danger()
                ->notify();
            return [];
        }

        // Check if the response has an error
        if (isset($data['error']) && $data['error'] !== null) {
            Log::channel('hd-wallet')->error('HD Wallet API Error: ' . $data['error']);
            Toast::message('خطا در سرویس کیف پول. لطفاً بعداً تلاش کنید.')
                ->danger()
                ->notify();
            return [];
        }

        // Check if response status is success
        if (isset($data['status']) && $data['status'] !== 'success') {
            Log::channel('hd-wallet')->error('HD Wallet API Status Error: ' . ($data['status'] ?? 'unknown'));
            Toast::message('خطا در سرویس کیف پول. لطفاً بعداً تلاش کنید.')
                ->danger()
                ->notify();
            return [];
        }

        $transactions = $data['transactions'] ?? [];

        return array_map(function (array $item) use ($requestDTO, $network) {
            $tokenSymbol = $requestDTO->getTokenSymbol();

            return resolve(GetDepositListsResponseDTO::class)
                ->setTimestamp($item['timestamp'])
                ->setCryptocurrency($tokenSymbol)
                ->setAmount((string) $item['value'])
                ->setTransactionHash($item['hash'])
                ->setStatus($item['status'])
                ->setConfirmationBlocks(isset($item['confirmations']) ? $item['confirmations'] : null)
                ->setBlockChain(strtoupper($network))
                ->setWalletAddress($requestDTO->getAddress())
                ->setContractAddress(isset($item['contract_address']) ? $item['contract_address'] : null)
                ->setType(isset($item['type']) ? $item['type'] : null)
                ->setBlockNumber(isset($item['block_number']) ? $item['block_number'] : null)
                ->setFrom($item['from'])
                ->setTo($requestDTO->getAddress())
                ->setGasPrice(isset($item['gas_price']) ? $item['gas_price'] : null)
                ->setGasUsed(isset($item['gas_used']) ? $item['gas_used'] : null);
        }, $transactions);
    }

    // ──────────────────────────────────────────────
    //  Withdrawal Operations
    // ──────────────────────────────────────────────

    /**
     * Create a new withdrawal request
     * 
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
                // 'memo' => $requestDTO->getWithdrawalId(),
                // 'remarks' => $requestDTO->getWithdrawalId(),
            ];

            $response = Http::post($this->baseUrl() . "/api/v1/wallet/withdrawals?symbol={$requestDTO->getCurrencySymbol()}&blockchain={$requestDTO->getBlockchain()}", $requestBody);
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

    /**
     * Get withdrawal status
     * 
     * @throws HDDWalletUnavailable
     * @throws NotFoundException
     * @throws InternalWalletHasProblemException
     */
    public function getStatus(GetWithdrawalStatusRequestDTO $requestDTO): GetWithdrawalStatusResponseDTO
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
}

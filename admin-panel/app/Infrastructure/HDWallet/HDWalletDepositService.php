<?php

namespace App\Infrastructure\HDWallet;

use App\Functions\FlashMessages\Toast;
use App\Infrastructure\HDWallet\DTO\HDDeposit\GetDepositListsRequestDTO;
use App\Infrastructure\HDWallet\DTO\HDDeposit\GetDepositListsResponseDTO;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HDWalletDepositService
{
    public function getDepositLists(GetDepositListsRequestDTO $requestDTO)
    {
        try {
            $network = $requestDTO->getBlockchain();
            $contractAddress = $requestDTO->getContractAddress();
            $requestBody = [
                'network' => $network,
                'token_symbol' => $requestDTO->getCurrencySymbol(),
                'contract_address' => $contractAddress,
                'address' => $requestDTO->getWalletAddress(),
                'limit' => $requestDTO->getLimit() ?? 50
            ];

            $response = Http::post(HDWallet::getBaseUrl() . '/api/v1/universal/deposit/transaction-history', $requestBody);
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
            $tokenSymbol = $requestDTO->getCurrencySymbol();

            return resolve(GetDepositListsResponseDTO::class)
                ->setTimestamp($item['timestamp'])
                ->setCryptocurrency($tokenSymbol)
                ->setAmount((string) $item['value'])
                ->setTransactionHash($item['hash'])
                ->setStatus($item['status'])
                ->setConfirmationBlocks(isset($item['confirmations']) ? $item['confirmations'] : null)
                ->setBlockChain(strtoupper($network))
                ->setWalletAddress($requestDTO->getWalletAddress())
                ->setContractAddress(isset($item['contract_address']) ? $item['contract_address'] : null)
                ->setType(isset($item['type']) ? $item['type'] : null)
                ->setBlockNumber(isset($item['block_number']) ? $item['block_number'] : null)
                ->setFrom($item['from'])
                ->setTo($requestDTO->getWalletAddress())
                ->setGasPrice(isset($item['gas_price']) ? $item['gas_price'] : null)
                ->setGasUsed(isset($item['gas_used']) ? $item['gas_used'] : null);
        }, $transactions);
    }
}

<?php

namespace App\Infrastructure\HDWallet;

use App\Exceptions\V1\Wallet\InternalWalletHasProblemException;
use App\Infrastructure\HDWallet\DTO\HDDeposit\GetDepositListsRequestDTO;
use App\Infrastructure\HDWallet\DTO\HDDeposit\GetDepositListsResponseDTO;
use App\Infrastructure\HDWallet\Exceptions\HDDWalletUnavailable;
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

        // Check if the response has an error
        if (isset($data['error']) && $data['error'] !== null) {
            Log::channel('hd-wallet')->error('HD Wallet API Error: ' . $data['error']);
            throw new InternalWalletHasProblemException;
        }

        // Check if response status is success
        if (isset($data['status']) && $data['status'] !== 'success') {
            Log::channel('hd-wallet')->error('HD Wallet API Status Error: ' . ($data['status'] ?? 'unknown'));
            throw new InternalWalletHasProblemException;
        }

        $transactions = $data['transactions'] ?? [];

        return array_map(function (array $item) use ($requestDTO, $network) {
            $tokenSymbol = $item['token_symbol'] ?? $requestDTO->getCurrencySymbol();

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

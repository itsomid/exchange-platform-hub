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
            $route = DepositApiRoutes::get($requestDTO->getCurrencySymbol(), $requestDTO->getBlockchain(), $requestDTO->getWalletAddress());
            $response = Http::get(HDWallet::getBaseUrl().$route);
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
            Log::channel('hd-wallet')->error('HD Wallet Response Changed:'.$response->body());
            throw new InternalWalletHasProblemException;
        }

        return array_map(fn (array $item) => resolve(GetDepositListsResponseDTO::class)
            ->setWalletId($item['wallet_id'])
            ->setUserId($item['user_id'])
            ->setTimestamp($item['timestamp'])
            ->setCryptocurrency($item['cryptocurrency'])
            ->setAmount((string) $item['deposit_amount'])
            ->setTransactionHash($item['transaction_hash'])
            ->setStatus($item['status'])
            ->setConfirmationBlocks($item['confirmation_blocks'])
            ->setBlockChain(CurrencyMapEnum::tryFrom($item['blockchain'])->name)
            ->setWalletAddress($item['wallet_address']), $data);
    }
}

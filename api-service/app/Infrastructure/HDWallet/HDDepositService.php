<?php

namespace App\Infrastructure\HDWallet;

use App\Exceptions\V1\Wallet\InternalWalletHasProblemException;
use App\Infrastructure\HDWallet\DTO\HDDeposit\GetDepositListsRequestDTO;
use App\Infrastructure\HDWallet\DTO\HDDeposit\GetDepositListsResponseDTO;
use Illuminate\Support\Facades\Http;

class HDDepositService
{
    public function getDepositLists(GetDepositListsRequestDTO $requestDTO)
    {
        $response = Http::get(HDWallet::getBaseUrl()."/api/v1/wallet/deposits/{$requestDTO->getCurrencySymbol()}/{$requestDTO->getWalletAddress()}/all");
        if ($response->serverError()) {
            report($response);
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
            ->setWalletAddress($item['wallet_address']), $response->json('items'));
    }
}

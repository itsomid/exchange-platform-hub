<?php

namespace App\Http\Resources\V1\Wallet;

use App\Services\Wallet\DTO\Wallet\WalletListsResponseDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class WalletListsCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return $this->collection->map(fn (WalletListsResponseDTO $walletListsResponseDTO) => [
            'id' => $walletListsResponseDTO->getId(),
            'currency' => $walletListsResponseDTO->getCurrency(),
            'balance' => $walletListsResponseDTO->getBalance(),
            'frozen_balance' => $walletListsResponseDTO->getLockedBalance(),
            'usdt_balance' => $walletListsResponseDTO->getUsdtBalance(),
            'usdt_frozen_balance' => $walletListsResponseDTO->getUsdtLockedBalance(),
        ])->toArray();
    }
}

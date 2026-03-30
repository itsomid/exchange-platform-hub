<?php

namespace App\Http\Resources\V1\Wallet;

use App\Services\Wallet\DTO\Wallet\WalletListsResponseDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @OA\Schema(
 *      schema="WalletListsListCollection",
 *
 *      @OA\Property(property="id", type="integer", example=1, description="Unique identifier of the wallet."),
 *      @OA\Property(property="currency", type="string", example="BTC", description="The currency symbol of the wallet."),
 *      @OA\Property(property="balance", type="string", example="0.12345", description="The available balance of the wallet."),
 *      @OA\Property(property="frozen_balance", type="string", example="0.01234", description="The frozen (locked) balance of the wallet."),
 *      @OA\Property(property="usdt_balance", type="string", example="500.25", description="The available balance of the wallet in USDT."),
 *      @OA\Property(property="usdt_frozen_balance", type="string", example="10.50", description="The frozen (locked) balance of the wallet in USDT.")
 *
 * )
 */
class WalletListsCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return $this->collection->map(fn(WalletListsResponseDTO $walletListsResponseDTO) => [
            'id' => $walletListsResponseDTO->getId(),
            'currency' => $walletListsResponseDTO->getCurrency(),
            'currency_logo' => $walletListsResponseDTO->getCurrencyLogo() ? config('bitexroom.currency_logo_base_url') . '/' . $walletListsResponseDTO->getCurrencyLogo() : null,
            'balance' => $walletListsResponseDTO->getBalance(),
            'frozen_balance' => $walletListsResponseDTO->getLockedBalance(),
            'available_balance' => $walletListsResponseDTO->getAvailableBalance(),
            'usdt_balance' => $walletListsResponseDTO->getUsdtBalance(),
            'usdt_frozen_balance' => $walletListsResponseDTO->getUsdtLockedBalance(),
        ])->toArray();
    }
}

<?php

namespace App\Http\Resources\V1\Wallet;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *      schema="GetOneWalletResponse",
 *      type="object",
 *      title="Wallet Balance Resource",
 *      description="Resource representing wallet details for a specific currency.",
 *      @OA\Property(property="currency", type="string", description="Currency symbol (e.g., BTC, ETH, USDT).", example="BTC"),
 *      @OA\Property(property="balance", type="number", format="float", description="Available balance in the wallet.", example=0.12345),
 *      @OA\Property(property="frozen_balance", type="number", format="float", description="Frozen balance in the wallet.", example=0.01),
 *      @OA\Property(property="usdt_balance", type="number", format="float", description="Equivalent USDT value of the available balance.", example=3000),
 *      @OA\Property(property="usdt_frozen_balance", type="number", format="float", description="Equivalent USDT value of the frozen balance.", example=100)
 *  )
 * /
 * @method string getCurrencySymbol()
 * @method string getBalance()
 * @method string getLockedBalance()
 * @method string getUsdtBalance()
 * @method string getUsdtLockedBalance()
 */
class GetOneWalletResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'currency' => $this->getCurrencySymbol(),
            'balance' => $this->getBalance(),
            'frozen_balance' => $this->getLockedBalance(),
            'usdt_balance' => $this->getUsdtBalance(),
            'usdt_frozen_balance' => $this->getUsdtLockedBalance(),
        ];
    }
}

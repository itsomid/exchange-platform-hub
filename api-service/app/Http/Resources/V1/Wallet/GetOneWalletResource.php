<?php

namespace App\Http\Resources\V1\Wallet;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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

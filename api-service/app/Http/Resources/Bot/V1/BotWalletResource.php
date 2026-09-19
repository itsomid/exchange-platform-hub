<?php

namespace App\Http\Resources\Bot\V1;

use App\Helpers\Math;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BotWalletResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $balance = (string) $this->balance;
        $locked  = (string) $this->locked_balance;

        $allocationUsedPercent = Math::comp($balance, '0') === 1
            ? Math::mul(Math::div($locked, $balance), '100')
            : '0.00000000';

        return [
            'balance'                => $balance,
            'locked_balance'         => $locked,
            'principal_balance'      => (string) $this->principal_balance,
            'profit_balance'         => (string) $this->profit_balance,
            'allocation_used_percent' => $allocationUsedPercent,
        ];
    }
}

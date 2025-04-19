<?php

namespace App\Http\Resources\Accounting\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user_email' => $this->user->email,
            'coin_type' => $this->wallet->currency_symbol,
            'otc_order_id' => $this->otc_order_id,
            'wallet_id' => $this->wallet_id,
            'amount' => $this->amount,
            'balance' => $this->balance,
            'type' => $this->type,
            'subtype' => $this->subtype,
            'status' => $this->status,
            'description' => $this->description,
            'created_at' => $this->created_at,
        ];
    }
}

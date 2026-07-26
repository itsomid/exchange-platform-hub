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
            'spot_trade_id' => $this->spot_trade_id,
            'stock_contract_id' => $this->stock_contract_id,
            'deposit_id' => $this->deposit_id,
            'withdrawal_id' => $this->withdrawal_id,
            'bot_order_id' => $this->bot_order_id,
            'price' => $this->coin_price,
            'wallet_id' => $this->wallet_id,
            'amount' => $this->amount,
            'balance' => $this->balance,
            'type' => $this->type,
            'subtype' => $this->subtype,
            'ref_exchange' => $this->exchange?->slug,
            'status' => $this->status,
            'description' => $this->description,
            'created_at' => $this->created_at,
        ];
    }
}

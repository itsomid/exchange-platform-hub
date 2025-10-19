<?php

namespace App\Http\Resources\V1\Wallet;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;


class WithdrawalListResource extends JsonResource
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
            'amount' => $this->amount,
            'fee' => $this->total_fee,
            'currency_symbol' => $this->currency_symbol,
            'currency_chain' => $this->currencyChain?->chain?->value ?? null,
            'currency_logo' => $this->currency?->logo ?? null,
            'transaction_hash' => $this->transaction_hash,
            'wallet_address' => $this->address,
            'type' => 'withdrawal',
            'type_lang' => __('enum.transaction-type.' . \App\Enums\TransactionTypeEnum::WITHDRAWAL->name),
            'status' => $this->status->value,
            'status_lang' => __('enum.withdrawal.' . $this->status->name),
            'explorer_address_url' => $this->explorer_address_url,
            'explorer_tx_url' => $this->explorer_tx_url,
            'confirmed_at' => $this->confirmed_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}

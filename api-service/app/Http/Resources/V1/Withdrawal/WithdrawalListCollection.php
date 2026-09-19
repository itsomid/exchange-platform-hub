<?php

namespace App\Http\Resources\V1\Withdrawal;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;


class WithdrawalListCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection->map(function ($withdrawal) {
                return [
                    'id' => $withdrawal->id,
                    'amount' => $withdrawal->amount,
                    'fee' => $withdrawal->total_fee,
                    'currency_symbol' => $withdrawal->currency_symbol,
                    'currency_chain' => $withdrawal->currencyChain?->chain?->value ?? null,
                    'currency_logo' => $withdrawal->currency?->logo ? config('bitexroom.currency_logo_base_url') . '/' . $withdrawal->currency->logo : null,
                    'transaction_hash' => $withdrawal->transaction_hash,
                    'wallet_address' => $withdrawal->address,
                    'type' => 'withdrawal',
                    'type_lang' => __('enum.transaction-type.' . \App\Enums\TransactionTypeEnum::WITHDRAWAL->name),
                    'status' => $withdrawal->status->value,
                    'status_lang' => __('enum.withdrawal.' . $withdrawal->status->name),
                    'explorer_address_url' => $withdrawal->explorer_address_url,
                    'explorer_tx_url' => $withdrawal->explorer_tx_url,
                    'confirmed_at' => $withdrawal->confirmed_at?->toISOString(),
                    'created_at' => $withdrawal->created_at?->toISOString(),
                ];
            }),
        ];
    }
}

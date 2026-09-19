<?php

namespace App\Http\Resources\V1\Deposit;

use App\Models\Deposit;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;


class DepositListCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return $this->collection->map(fn(Deposit $deposit) => [
            'id' => $deposit->id,
            'amount' => $deposit->amount,
            'currency_symbol' => $deposit->currency_symbol,
            'currency_chain' => $deposit->currencyChain?->chain_name,
            'currency_logo' => $deposit->currency?->logo ? config('bitexroom.currency_logo_base_url') . '/' . $deposit->currency->logo : null,
            'transaction_hash' => $deposit->transaction_hash,
            'wallet_address' => $deposit->address,
            'status' => $deposit->status->name ?? $deposit->status,
            'status_lang' => __('enum.deposit.' . ($deposit->status->name ?? $deposit->status)),
            'explorer_address_url' => $deposit->explorer_address_url,
            'explorer_tx_url' => $deposit->explorer_tx_url,
            'confirmed_at' => $deposit->confirmed_at,
            'created_at' => $deposit->created_at,
            'usdt_value' => $deposit->usdt_value,
        ])->toArray();
    }
}

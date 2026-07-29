<?php

namespace App\Http\Resources\Accounting\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CurrencyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'logo' => $this->logo
                ? config('bitexroom.currency_logo_base_url') . '/' . $this->logo
                : null,
            'is_active' => (bool) $this->is_active,
            'persian_name' => $this->persian_name,
            'name' => $this->name,
            'symbol' => $this->symbol,
            'price_precision' => $this->price_precision,
            'amount_precision' => $this->amount_precision,
            'chains' => $this->chains->map(fn ($chain) => [
                'chain' => $chain->chain?->value ?? $chain->chain,
                'chain_name' => $chain->chain_name,
                'blockchain_name' => $chain->blockchain_name,
                'is_base_coin' => (bool) $chain->is_base_coin,
            ])->values(),
        ];
    }
}

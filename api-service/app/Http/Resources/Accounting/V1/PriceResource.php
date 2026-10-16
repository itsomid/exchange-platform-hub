<?php

namespace App\Http\Resources\Accounting\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PriceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'base_currency' => $this->market->base_currency,
            'quote_currency' => $this->market->quote_currency,
            'price' => $this->price,
            'buy_price' => $this->buy_price,
            'sell_price' => $this->sell_price,
        ];
    }
}

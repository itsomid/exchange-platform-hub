<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SpotOrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'market' => $this->market->base_currency.'|'.$this->market->quote_currency,
            'side' => $this->side,
            'type' => $this->type,
            'quantity' => $this->quantity,
            'price' => $this->price,
            'status' => $this->status,
            'filled_quantity' => $this->filled_quantity,
        ];
    }
}

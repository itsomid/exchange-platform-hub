<?php

namespace App\Http\Resources\V1\Spot;

use App\Helpers\Math;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;


class OrderBookResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Find maximum quantity in asks and bids for depth calculation
        $maxAsksQuantity = $this->resource['asks']->max('quantity') ?: 1;
        $maxBidsQuantity = $this->resource['bids']->max('quantity') ?: 1;

        return [
            'asks' => $this->resource['asks']->map(fn ($item) => [
                'price' => $item->price,
                'filled_quantity' => $item->filled_quantity,
                'quantity' => $item->quantity,
                'total' => $item->price ? Math::mul($item->price, $item->quantity) : null,
                'depth_percent' => $item->quantity ? 
                    Math::div($item->quantity, $maxAsksQuantity) * 100 : 0
            ]),
            'bids' => $this->resource['bids']->map(fn ($item) => [
                'price' => $item->price,
                'filled_quantity' => $item->filled_quantity,
                'quantity' => $item->quantity,
                'total' => $item->price ? Math::mul($item->price, $item->quantity) : null,
                'depth_percent' => $item->quantity ? 
                    Math::div($item->quantity, $maxBidsQuantity) * 100 : 0
            ]),
        ];
    }
}

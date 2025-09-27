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
        return [
            'asks' => $this->resource['asks']->map(fn ($item) => [
                'price' => $item->price,
                'filled_quantity' => $item->filled_quantity,
                'quantity' => $item->quantity,
                'total' => $item->price ? Math::mul($item->price, $item->quantity) : null,
                'depth_percent' =>
                    $item->price?
                    Math::div($item->filled_quantity,$item->quantity) * 100 : null
            ]),
            'bids' => $this->resource['bids']->map(fn ($item) => [
                'price' => $item->price,
                'filled_quantity' => $item->filled_quantity,
                'quantity' => $item->quantity,
                'total' => $item->price ? Math::mul($item->price, $item->quantity) : null,
                'depth_percent' =>
                    $item->price?
                    Math::div($item->filled_quantity,$item->quantity) * 100 : null
            ]),
        ];
    }
}

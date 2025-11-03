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
        // Convert to collection if it's array (from HybridOrderBookService)
        $asks = is_array($this->resource['asks']) 
            ? collect($this->resource['asks']) 
            : $this->resource['asks'];
        
        $bids = is_array($this->resource['bids']) 
            ? collect($this->resource['bids']) 
            : $this->resource['bids'];

        // Find maximum quantity in asks and bids for depth calculation
        $maxAsksQuantity = $asks->max('quantity') ?: 1;
        $maxBidsQuantity = $bids->max('quantity') ?: 1;

        return [
            'asks' => $asks->map(fn ($item) => [
                'price' => is_object($item) ? $item->price : $item['price'],
                'quantity' => is_object($item) ? $item->quantity : $item['quantity'],
                'total' => isset($item->total) || (isset($item['total']) && $item['total'])
                    ? (is_object($item) ? ($item->total ?? null) : ($item['total'] ?? null))
                    : (is_object($item) && isset($item->price) ? Math::mul($item->price, $item->quantity) : null),
                'depth_percent' => (is_object($item) ? $item->quantity : $item['quantity']) 
                    ? Math::div((is_object($item) ? $item->quantity : $item['quantity']), (string)$maxAsksQuantity) * 100 
                    : 0
            ]),
            'bids' => $bids->map(fn ($item) => [
                'price' => is_object($item) ? $item->price : $item['price'],
                'quantity' => is_object($item) ? $item->quantity : $item['quantity'],
                'total' => isset($item->total) || (isset($item['total']) && $item['total'])
                    ? (is_object($item) ? ($item->total ?? null) : ($item['total'] ?? null))
                    : (is_object($item) && isset($item->price) ? Math::mul($item->price, $item->quantity) : null),
                'depth_percent' => (is_object($item) ? $item->quantity : $item['quantity']) 
                    ? Math::div((is_object($item) ? $item->quantity : $item['quantity']), (string)$maxBidsQuantity) * 100 
                    : 0
            ]),
        ];
    }
}

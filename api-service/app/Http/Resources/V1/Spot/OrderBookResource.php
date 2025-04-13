<?php

namespace App\Http\Resources\V1\Spot;

use App\Helpers\Math;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="OrderBookResource",
 *     type="object",
 *
 *     @OA\Property(
 *         property="asks",
 *         type="array",
 *
 *         @OA\Items(
 *
 *             @OA\Property(property="price", type="number", format="float", example=50000.50),
 *              @OA\Property(property="filled_quantity", type="number", format="float", example=0.75),
 *              @OA\Property(property="quantity", type="number", format="float", example=1.25)
 *         )
 *     ),
 *     @OA\Property(
 *         property="bids",
 *         type="array",
 *
 *         @OA\Items(
 *
 *             @OA\Property(property="price", type="number", format="float", example=50000.50),
 *              @OA\Property(property="filled_quantity", type="number", format="float", example=0.75),
 *              @OA\Property(property="quantity", type="number", format="float", example=1.25)
 *         )
 *     )
 * )
 */
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
                    Math::div($item->filled_quantity,$item->quantity) : null
            ]),
            'bids' => $this->resource['bids']->map(fn ($item) => [
                'price' => $item->price,
                'filled_quantity' => $item->filled_quantity,
                'quantity' => $item->quantity,
                'total' => $item->price ? Math::mul($item->price, $item->quantity) : null,
                'depth_percent' =>
                    $item->price?
                    Math::div($item->filled_quantity,$item->quantity) : null
            ]),
        ];
    }
}

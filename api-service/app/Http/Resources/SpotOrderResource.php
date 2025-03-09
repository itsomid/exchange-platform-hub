<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="SpotOrderResource",
 *
 *     @OA\Property(property="market", type="string", example="BTC|USD", description="Trading pair in BASE|QUOTE format"),
 *     @OA\Property(property="side", type="string", example="buy", enum={"buy", "sell"}),
 *     @OA\Property(property="type", type="string", example="limit", enum={"limit", "market"}),
 *     @OA\Property(property="quantity", type="number", format="float", example=0.5),
 *     @OA\Property(property="price", type="number", format="float", example=45000.50),
 *     @OA\Property(property="status", type="string", example="filled", description="Order status"),
 *     @OA\Property(property="filled_quantity", type="number", format="float", example=0.5)
 * )
 */
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

<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="SpotOrderResource",
 *
 *     @OA\Property(property="id", type="number", example="1", description="order id"),
 *     @OA\Property(property="market", type="string", example="BTC|USDT", description="Trading pair in BASE|QUOTE format"),
 *     @OA\Property(property="side", type="string", example="buy", enum={"buy", "sell"}),
 *     @OA\Property(property="type", type="string", example="limit", enum={"limit", "market"}),
 *     @OA\Property(property="quantity", type="number", format="float", example=0.5),
 *     @OA\Property(property="price", type="number", format="float", example=45000.50),
 *     @OA\Property(property="status", type="string", example="filled", description="Order status"),
 *     @OA\Property(property="filled_quantity", type="number", format="float", example=0.5),
 *     @OA\Property(property="commission", type="number", format="float", example=0.001),
 *     @OA\Property(property="filled_value", type="number", format="float", example=5.2, description="filled value in USDT"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2023-12-21T14:00:00Z")
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
            'id' => $this->getId(),
            'market' => $this->getMarketName(),
            'side' => $this->getSide(),
            'type' => $this->getType(),
            'quantity' => $this->getQuantity(),
            'price' => $this->getPrice() ?? 'market',
            'status' => __('enum.spot.status.'.$this->getStatus()->name),
            'filled_quantity' => $this->getFilledQuantity(),
            'commission' => $this->getCommission(),
            'filled_value' => $this->getFilledValue(),
            'created_at' => $this->getCreatedAt(),
        ];
    }
}

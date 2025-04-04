<?php

namespace App\Http\Resources\V1\Spot;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="TradeResource",
 *     type="object",
 *
 *     @OA\Property(
 *         property="price",
 *         type="number",
 *         format="float",
 *         example=50000.50
 *     ),
 *     @OA\Property(
 *         property="quantity",
 *         type="number",
 *         format="float",
 *         example=0.5
 *     ),
 *     @OA\Property(
 *         property="side",
 *         enum={"buy", "sell"},
 *         example="buy"
 *       ),
 *     @OA\Property(
 *         property="created_at",
 *         type="string",
 *         format="date-time",
 *         example="2023-08-15T14:30:45Z"
 *     )
 * )
 */
class TradeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'price' => $this->price,
            'quantity' => $this->quantity,
            'side' => $this->makerOrder->side,
            'created_at' => $this->created_at,
        ];
    }
}

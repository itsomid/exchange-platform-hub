<?php

namespace App\Http\Resources\V1\OTC;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="AvailableCoin",
 *     type="object",
 *     title="AvailableCoin",
 *     description="List of available coins for a specific market.",
 *     @OA\Property(
 *         property="available",
 *         type="array",
 *         description="List of available coins.",
 *         @OA\Items(type="string", example="BTC")
 *     )
 * )
 */
class AvailableCoinResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'available' => $this->resource
        ];
    }
}

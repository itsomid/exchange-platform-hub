<?php

namespace App\Http\Resources\V1\OTC;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="FeeResource",
 *     type="object",
 *     title="OTC Fees",
 *     description="Representation of the buy and sell fees for OTC trades.",
 *
 *     @OA\Property(
 *         property="buy_fee",
 *         type="string",
 *         description="The fee for buying in OTC trades.",
 *         example="0.001"
 *     ),
 *     @OA\Property(
 *         property="sell_fee",
 *         type="string",
 *         description="The fee for selling in OTC trades.",
 *         example="0.002"
 *     )
 * )
 */
class FeeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'buy_fee' => $this->resource['buy'],
            'sell_fee' => $this->resource['sell'],
        ];
    }
}

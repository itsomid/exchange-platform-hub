<?php

namespace App\Http\Resources\V1\Wallet;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="CoinAddressResponse",
 *     type="object",
 *
 *     @OA\Property(
 *         property="address",
 *         type="string",
 *         example="1A1zP1eP5QGefi2DMPTfTL5SLmv7DivfNa",
 *         description="The generated wallet address for the user."
 *     )
 * )
 */
class CoinAddressResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'address' => $this->getAddress(),
        ];
    }
}

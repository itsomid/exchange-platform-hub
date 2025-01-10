<?php

namespace App\Http\Resources\V1\Wallet;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @OA\Schema(
 *     schema="SavedAddressCollection",
 *     type="object",
 *
 *     @OA\Property(
 *         property="data",
 *         type="array",
 *
 *         @OA\Items(
 *             type="object",
 *
 *             @OA\Property(property="id", type="integer", example=1),
 *             @OA\Property(property="user", type="integer", example=42),
 *             @OA\Property(property="name", type="string", example="Main Wallet"),
 *             @OA\Property(property="chain", type="string", example="BTC"),
 *             @OA\Property(property="chain_name", type="string", example="Bitcoin"),
 *             @OA\Property(property="address", type="string", example="1A1zP1eP5QGefi2DMPTfTL5SLmv7DivfNa"),
 *             @OA\Property(property="created_at", type="string", format="date-time", example="2023-12-21T14:00:00Z"),
 *             @OA\Property(property="updated_at", type="string", format="date-time", example="2023-12-22T14:00:00Z")
 *         )
 *     )
 * )
 */
class SavedAddressCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection->map(function ($address) {
                return [
                    'id' => $address->id,
                    'user' => $address->user->id,
                    'name' => $address->name,
                    'chain' => $address->chain,
                    'chain_name' => $address->currencyChain->chain_name ?? null,
                    'address' => $address->address,
                    'created_at' => $address->created_at->toDateTimeString(),
                    'updated_at' => $address->updated_at->toDateTimeString(),
                ];
            }),
        ];
    }
}

<?php

namespace App\Http\Resources\V1\Wallet;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

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
                    'chain_name' => $address->currencyChain->chain_name  ?? null,
                    'address' => $address->address,
                    'created_at' => $address->created_at->toDateTimeString(),
                    'updated_at' => $address->updated_at->toDateTimeString(),
                ];
            }),
        ];
    }
}

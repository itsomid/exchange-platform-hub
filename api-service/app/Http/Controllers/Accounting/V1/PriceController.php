<?php

namespace App\Http\Controllers\Accounting\V1;

use App\Http\Resources\Accounting\V1\PriceResource;
use App\Models\Market;

class PriceController
{
    public function show(string $symbol)
    {
        $market = Market::query()
            ->where('base_currency', strtoupper($symbol))
            ->where('is_active', true)
            ->with('exchangePrice')
            ->orderBy('id')
            ->first();

        if (! $market || ! $market->exchangePrice) {
            return response([
                'message' => 'Price not found for the given symbol.',
            ], 404);
        }

        return new PriceResource($market->exchangePrice);
    }
}

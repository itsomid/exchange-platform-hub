<?php

namespace App\Http\Controllers\Accounting\V1;

use App\Http\Resources\Accounting\V1\CurrencyResource;
use App\Models\Currency;

class CurrencyController
{
    public function index()
    {
        $currencies = Currency::query()
            ->with('chains')
            ->orderBy('id')
            ->get();

        return CurrencyResource::collection($currencies);
    }
}

<?php

namespace App\Http\Controllers\V1\Currency;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class CurrencyListController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $currencies = Cache::remember('currency_logo_list', now()->addDays(30), function () {
            return Currency::whereNotNull('logo')
                ->where('logo', '!=', '')
                ->select(['id', 'name', 'symbol', 'logo'])
                ->get()
                ->map(fn ($currency) => [
                    'id'        => $currency->id,
                    'name'      => $currency->name,
                    'symbol'    => $currency->symbol,
                    'logo_url'  => config('bitexroom.currency_logo_base_url') . '/' . $currency->logo,
                ]);
        });

        return response()->json(['data' => $currencies]);
    }
}

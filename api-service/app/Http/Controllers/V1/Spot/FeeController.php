<?php

namespace App\Http\Controllers\V1\Spot;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;

class FeeController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'data' => [
                'taker_fee' => Setting::getSetting('spot_taker_fee'),
                'maker_fee' => Setting::getSetting('spot_maker_fee'),
            ],
        ]);
    }
}

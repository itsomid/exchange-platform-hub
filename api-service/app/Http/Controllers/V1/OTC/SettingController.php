<?php

namespace App\Http\Controllers\V1\OTC;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\OTC\FeeResource;
use App\Models\Setting;

class SettingController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/otc/fee",
     *     summary="Get OTC Buy and Sell Fees",
     *     description="Retrieve the buy and sell fees for OTC trades.",
     *     tags={"OTC"},
     *     security={{"sanctum": {}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Buy and Sell fees for OTC trades.",
     *         @OA\JsonContent(ref="#/components/schemas/FeeResource")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized, user is not authenticated.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function fee()
    {
        return new FeeResource([
            'buy' => Setting::getSetting('otc_buy_fee'),
            'sell' => Setting::getSetting('otc_sell_fee'),
        ]);
    }
}

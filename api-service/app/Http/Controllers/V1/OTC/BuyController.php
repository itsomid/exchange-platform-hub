<?php

namespace App\Http\Controllers\V1\OTC;

use App\Events\OTCOrderCreated;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\OTC\OTCBuyRequest;
use App\Services\OTC\DTO\OTCBuyRequestDTO;
use App\Services\OTC\OTCService;
use Illuminate\Support\Facades\Auth;

class BuyController extends Controller
{
    public function __construct(private readonly OTCService $OTCService) {}

    /**
     * @OA\Post(
     *     path="/api/v1/otc/buy",
     *     summary="Buy coins in OTC",
     *     description="Submit a buy order in the OTC market for the specified market and quantity.",
     *     tags={"OTC"},
     *     security={{"sanctum": {}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(ref="#/components/schemas/OTCBuyRequest")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Buy order submitted successfully.",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Buy order submitted successfully.")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation failed for the request.",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="market_id",
     *                     type="array",
     *
     *                     @OA\Items(type="string", example="The selected market_id is invalid.")
     *                 ),
     *
     *                 @OA\Property(
     *                     property="quantity",
     *                     type="array",
     *
     *                     @OA\Items(type="string", example="The quantity must be at least 0.")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function create(OTCBuyRequest $request)
    {
        $validateData = $request->validated();

        $this->OTCService->buy(
            resolve(OTCBuyRequestDTO::class)
                ->setMarketId($validateData['market_id'])
                ->setBuyerUserId(Auth::id())
                ->setSellerUserId(config('bitexroom.bitexroom_user_id'))
                ->setQuantity($validateData['quantity'])
        );

        event(new OTCOrderCreated);

        return response([
            'message' => __('otc.buy_order_submitted'),
        ]);
    }
}

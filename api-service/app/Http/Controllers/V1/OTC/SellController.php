<?php

namespace App\Http\Controllers\V1\OTC;

use App\Events\OTCOrderCreated;
use App\Http\Requests\V1\OTC\OTCSellRequest;
use App\Services\OTC\DTO\OTCSellRequestDTO;
use App\Services\OTC\OTCService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class SellController
{
    public function __construct(private readonly OTCService $OTCService) {}

    /**
     * @OA\Post(
     *     path="/api/v1/otc/sell",
     *     summary="Create OTC Sell Order",
     *     description="Submit a sell order for a coin in the OTC exchange.",
     *     tags={"OTC"},
     *     security={{"sanctum": {}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *          @OA\JsonContent(ref="#/components/schemas/OTCSellRequest")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Sell order submitted successfully.",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="The sell order has been submitted successfully."
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation failed for the request.",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="The given data was invalid."
     *             ),
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
     *     ),
     * )
     */
    public function create(OTCSellRequest $request)
    {
        $validateData = $request->validated();
        $lock = Cache::lock('order-sell:'.$validateData['market_id'].Auth::id(), 10);

        if ($lock->get()) {
            try {
                $responseDTO = $this->OTCService->sell(
                    resolve(OTCSellRequestDTO::class)
                        ->setMarketId($validateData['market_id'])
                        ->setSellerUserId(Auth::id())
                        ->setBuyerUserId(config('bitexroom.bitexroom_user_id'))
                        ->setQuantity($validateData['quantity'])
                );

                event(new OTCOrderCreated($responseDTO->getOtcOrderModel(), 'sell'));

                return response([
                    'message' => __('otc.sell_order_submitted'),
                ]);
            } catch (Throwable $exception) {
                $lock->release();
                throw $exception;
            } finally {
                $lock->release();
            }
        }

        return response([
            'message' => __('auth.too_many_attempts'),
        ], Response::HTTP_TOO_MANY_REQUESTS);
    }
}

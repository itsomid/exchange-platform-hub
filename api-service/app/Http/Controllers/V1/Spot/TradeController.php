<?php

namespace App\Http\Controllers\V1\Spot;

use App\Enums\SpotOrderSideEnum;
use App\Enums\SpotOrderTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Spot\CreateOrderRequest;
use App\Http\Requests\V1\Spot\ListOrderRequest;
use App\Http\Resources\SpotOrderResource;
use App\Services\Spot\DTO\SpotTradeListsRequestDTO;
use App\Services\Spot\DTO\SpotTradeRequestDTO;
use App\Services\Spot\SpotService;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class TradeController extends Controller
{
    /**
     * @OA\Post(
     *     path="/orders",
     *     tags={"Spot Orders"},
     *     summary="Create a new trading order",
     *     description="Create a new spot trading order in the exchange",
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(ref="#/components/schemas/OrderRequest")
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Order created successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Order created successfully")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 example={"market_id": {"The market id field is required."}}
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=400,
     *         description="Market validation error",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Invalid market selected")
     *         )
     *     )
     * )
     */
    public function store(CreateOrderRequest $request)
    {
        $validated = $request->validated();
        $spotOrder = resolve(SpotService::class);
        $spotOrder->trade(
            resolve(SpotTradeRequestDTO::class)
                ->setUserId(Auth::id())
                ->setQuantity($validated['quantity'])
                ->setType(SpotOrderTypeEnum::tryFrom($validated['type']))
                ->setSide(SpotOrderSideEnum::tryFrom($validated['side']))
                ->setPrice($validated['price'])
                ->setMarketId($validated['market_id'])
        );

        return response([
            'message' => __('spot.order_created'),
        ], Response::HTTP_CREATED);
    }

    public function lists(ListOrderRequest $request)
    {
        $spotOrder = resolve(SpotService::class);
        $lists = $spotOrder->lists(
            resolve(SpotTradeListsRequestDTO::class)
                ->setUserId(Auth::id())
                ->setSide(
                    $request->has('side') ?
                    SpotOrderSideEnum::tryFrom(
                        $request->input('side')
                    )
                        :
                        null
                )
                ->setType(
                    $request->has('type') ?
                    SpotOrderTypeEnum::tryFrom(
                        $request->input('type')
                    )
                        :
                        null
                )
        );

        return SpotOrderResource::collection($lists);
    }
}

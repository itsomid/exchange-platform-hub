<?php

namespace App\Http\Controllers\V1\Spot;

use App\Enums\SpotOrderSideEnum;
use App\Enums\SpotOrderTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Spot\CreateOrderRequest;
use App\Http\Requests\V1\Spot\ListOrderRequest;
use App\Http\Resources\SpotOrderResource;
use App\Services\Spot\DTO\SpotOrderListsRequestDTO;
use App\Services\Spot\DTO\SpotOrderRequestDTO;
use App\Services\Spot\OrderMatchingEngine;
use App\Services\Spot\SpotService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class OrderController extends Controller
{
    public function __construct(private readonly SpotService $spotService) {}

    /**
     * @OA\Post(
     *     path="/api/v1/spot/orders",
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
        $lock = Cache::lock('trade:'.$validated['market_id'].Auth::id(), 40);
        if ($lock->get()) {
            try {
                $type = SpotOrderTypeEnum::tryFrom($validated['type']);
                $spotOrder = resolve(SpotService::class);
                $response = $spotOrder->trade(
                    resolve(SpotOrderRequestDTO::class)
                        ->setUserId(Auth::id())
                        ->setQuantity($validated['quantity'])
                        ->setType($type)
                        ->setSide(SpotOrderSideEnum::tryFrom($validated['side']))
                        ->setPrice($validated['price'])
                        ->setMarketId($validated['market_id'])
                );

                $orderMatchingEngine = resolve(OrderMatchingEngine::class);
                if ($type === SpotOrderTypeEnum::MARKET) {
                    $orderMatchingEngine->market($response->getSpotOrderModel());
                } elseif ($type === SpotOrderTypeEnum::LIMIT) {
                    $orderMatchingEngine->limit($response->getSpotOrderModel());
                }

                return response([
                    'message' => __('spot.order_created'),
                ], Response::HTTP_CREATED);
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

    /**
     * @OA\Get(
     *     path="/api/v1/spot/orders",
     *     tags={"Spot Orders"},
     *     summary="Get user's spot orders",
     *     description="Retrieve a list of authenticated user's spot orders with optional filters",
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Filter by order type",
     *         required=false,
     *
     *         @OA\Schema(
     *             type="string",
     *             enum={"market", "limit"}
     *         )
     *     ),
     *
     *     @OA\Parameter(
     *         name="side",
     *         in="query",
     *         description="Filter by order side",
     *         required=false,
     *
     *         @OA\Schema(
     *             type="string",
     *             enum={"buy", "sell"}
     *         )
     *     ),
     *
     *          @OA\Parameter(
     *          name="status",
     *          in="query",
     *          description="Filter by order status",
     *          required=false,
     *
     *          @OA\Schema(
     *              type="string",
     *              enum={"open", "completed"}
     *          )
     *      ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="List of spot orders",
     *
     *         @OA\JsonContent(
     *             type="array",
     *
     *             @OA\Items(ref="#/components/schemas/SpotOrderResource")
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
     *                 example={"type": {"The selected type is invalid."}}
     *             )
     *         )
     *     )
     * )
     */
    public function lists(ListOrderRequest $request)
    {
        $lists = $this->spotService->lists(
            resolve(SpotOrderListsRequestDTO::class)
                ->setUserId(Auth::id())
                ->setSide($request->input('side'))
                ->setType($request->input('type'))
                ->setStatus($request->input('status'))
        );

        return SpotOrderResource::collection($lists);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/spot/orders/{orderId}",
     *     tags={"Spot Orders"},
     *     summary="Get spot order details",
     *     description="Retrieve order detilas",
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="List of spot orders",
     *
     *     @OA\JsonContent(
     *
     *           @OA\Property(property="data",type="object", ref="#/components/schemas/SpotOrderResource"),
     *     )
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
     *                 example={"type": {"The selected type is invalid."}}
     *             )
     *         )
     *     )
     * )
     */
    public function show(int $orderId)
    {
        return new SpotOrderResource(
            $this->spotService->getDetail(
                userId: Auth::id(),
                orderId: $orderId
            )
        );
    }
}

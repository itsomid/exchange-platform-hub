<?php

namespace App\Http\Controllers\V1\Spot;

use App\Enums\SpotOrderSideEnum;
use App\Enums\SpotOrderTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Spot\CreateOrderRequest;
use App\Http\Requests\V1\Spot\ListOrderRequest;
use App\Http\Resources\SpotOrderResource;
use App\Http\Resources\V1\Spot\OrderBookResource;
use App\Models\SpotOrder;
use App\Services\Spot\DTO\SpotOrderListsRequestDTO;
use App\Services\Spot\DTO\SpotOrderRequestDTO;
use App\Services\Spot\OrderMatchingEngine;
use App\Services\Spot\SpotService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
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
     *          description="Filter by order status. 'completed' also includes 'partially_filled_canceled'.", // Updated description
     *          required=false,
     *
     *          @OA\Schema(
     *              type="string",
     *              // Ensure these enum values match your actual status strings
     *              enum={"open", "completed", "canceled", "partially_filled", "partially_filled_canceled"}
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
    public function show(SpotOrder $order)
    {
        if (! Gate::allows('update-spot-order', $order)) {
            abort(403);
        }

        return new SpotOrderResource(
            $this->spotService->getDetail(
                userId: Auth::id(),
                orderId: $order->id
            )
        );
    }

    /**
     * @OA\Post(
     *     path="/api/v1/spot/orders/cancel/{order}",
     *     tags={"Spot Orders"},
     *     summary="Cancel a spot order",
     *     description="Cancel an existing spot order by its ID",
     *     operationId="cancelSpotOrder",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="order",
     *         in="path",
     *         description="ID of the order to cancel",
     *         required=true,
     *
     *         @OA\Schema(
     *             type="integer",
     *             format="int64"
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Order canceled successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Order canceled successfully"
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="You are not authorized to cancel this order"
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Order not found",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Order not found"
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Internal server error occurred"
     *             )
     *         )
     *     )
     * )
     */
    public function cancel(SpotOrder $order)
    {
        if (! Gate::allows('update-spot-order', $order)) {
            abort(403);
        }

        $this->spotService->cancel(
            userId: Auth::id(),
            orderId: $order->id
        );

        return response([
            'message' => __('spot.order_canceled'),
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/spot/order-books/{marketId}",
     *     tags={"Spot Orders"},
     *     summary="Get market order book",
     *     description="Returns the current order book (asks and bids) for a specific market",
     *     operationId="getOrderBooks",
     *
     *     @OA\Parameter(
     *         name="marketId",
     *         in="path",
     *         description="ID of the market",
     *         required=true,
     *
     *         @OA\Schema(
     *             type="integer",
     *             format="int64",
     *             example=1
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *
     *         @OA\JsonContent(ref="#/components/schemas/OrderBookResource")
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Market not found",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Market not found")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Internal server error")
     *         )
     *     )
     * )
     */
    public function getOrderBooks(int $marketId)
    {
        return new OrderBookResource($this->spotService->getLatestOrderBook(
            marketId: $marketId,
            limit: config('spot.order_book_limit_count')
        ));
    }
}

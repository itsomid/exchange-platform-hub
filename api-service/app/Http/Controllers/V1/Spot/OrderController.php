<?php

namespace App\Http\Controllers\V1\Spot;

use App\Enums\SpotOrderSideEnum;
use App\Enums\SpotOrderTypeEnum;
use App\Enums\SpotOrderSourceEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Spot\CreateOrderRequest;
use App\Http\Requests\V1\Spot\ListOrderRequest;
use App\Http\Resources\SpotOrderResource;
use App\Http\Resources\V1\Spot\OrderBookResource;
use App\Models\Setting;
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

    public function store(CreateOrderRequest $request)
    {
        // Check if spot trading is enabled
        if (!Setting::isEnabled('spot_trading_enabled')) {
            return response([
                'message' => __('spot.trading_disabled'),
            ], Response::HTTP_FORBIDDEN);
        }

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
                        ->setSource(SpotOrderSourceEnum::USER)
                );

                $orderMatchingEngine = resolve(OrderMatchingEngine::class);
                $spotOrder = $response->getSpotOrderModel();

                if ($spotOrder === null) {
                    throw new \RuntimeException('Failed to create spot order');
                }

                if ($type === SpotOrderTypeEnum::MARKET) {
                    $orderMatchingEngine->market($spotOrder);
                } elseif ($type === SpotOrderTypeEnum::LIMIT) {
                    $orderMatchingEngine->limit($spotOrder);
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

    public function lists(ListOrderRequest $request)
    {
        $lists = $this->spotService->lists(
            resolve(SpotOrderListsRequestDTO::class)
                ->setUserId(Auth::id())
                ->setSide($request->input('side'))
                ->setType($request->input('type'))
                ->setStatus($request->input('status'))
                ->setMarketId($request->input('market_id'))
        );

        return SpotOrderResource::collection($lists);
    }


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

    public function cancel(SpotOrder $order)
    {
        if (! Gate::allows('update-spot-order', $order)) {
            return response()->json([
                'message' => __('auth.access_denied'),
            ], 403);
        }

        try {
            $this->spotService->cancel(
                userId: Auth::id(),
                orderId: $order->id
            );

            return response()->json([
                'message' => __('spot.order_canceled'),
            ]);
        } catch (Throwable $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 400);
        }
    }


    public function getOrderBooks(int $marketId)
    {
        return new OrderBookResource($this->spotService->getLatestOrderBook(
            marketId: $marketId,
            limit: config('spot.order_book_limit_count')
        ));
    }
}

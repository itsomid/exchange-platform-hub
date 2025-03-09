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
    public function store(CreateOrderRequest $request)
    {
        $validated = $request->validated();
        //TODO
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

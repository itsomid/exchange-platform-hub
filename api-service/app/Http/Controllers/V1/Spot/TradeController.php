<?php

namespace App\Http\Controllers\V1\Spot;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\Spot\TradeResource;
use App\Services\Spot\TradeService;

class TradeController extends Controller
{
    public function __construct(private readonly TradeService $tradeService) {}


    public function getLatestMatched(int $marketId)
    {
        return TradeResource::collection(
            $this->tradeService->getLatestMatched($marketId)
        );
    }
}

<?php

namespace App\Http\Controllers\V1\Spot;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\OTC\MarketCollection;
use App\Services\Exchanges\MarketState\MarketStateService;
use App\Services\OTC\OTCService;

class MarketController extends Controller
{
    public function __construct(private readonly OTCService $OTCService) {}

  
    public function lists(): MarketCollection
    {
        return new MarketCollection($this->OTCService->markets());
    }

    /**
     * @OA\Get(
     *     path="/api/v1/markets/{marketId}/state",
     *     tags={"Spot Orders"},
     *     summary="Get market state statistics",
     *     description="Returns key statistics for a specific market including price, volume, and changes",
     *     operationId="getMarketState",
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
     *         description="Market state data",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="low", type="number", format="float", example=1567.00),
     *             @OA\Property(property="high", type="number", format="float", example=1816.74),
     *             @OA\Property(property="volume", type="number", format="float", example=0.00),
     *             @OA\Property(property="last", type="number", format="float", example=1590.30),
     *             @OA\Property(property="open", type="number", format="float", example=1793.43),
     *             @OA\Property(
     *                 property="price_change_percentage",
     *                 type="number",
     *                 format="float",
     *                 example=-11.33,
     *                 description="24h price change percentage"
     *             )
     *         )
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
    public function getState(int $marketId)
    {
        $marketStateService = resolve(MarketStateService::class);

        return response($marketStateService->getState($marketId));
    }
}

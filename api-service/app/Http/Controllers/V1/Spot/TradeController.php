<?php

namespace App\Http\Controllers\V1\Spot;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\Spot\TradeResource;
use App\Services\Spot\TradeService;

class TradeController extends Controller
{
    public function __construct(private readonly TradeService $tradeService) {}

    /**
     * @OA\Get(
     *     path="/api/v1/spot/trades/{marketId}/latest",
     *     tags={"Spot Orders"},
     *     summary="Get latest matched trades for a market",
     *     description="Returns a list of the most recent matched trades in the spot market",
     *     operationId="getLatestMatched",
     *
     *     @OA\Parameter(
     *         name="marketId",
     *         in="path",
     *         description="ID of the market",
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
     *         description="Successful operation",
     *
     *         @OA\JsonContent(
     *             type="array",
     *
     *             @OA\Items(ref="#/components/schemas/TradeResource")
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
     *         description="Server error"
     *     )
     * )
     */
    public function getLatestMatched(int $marketId)
    {
        return TradeResource::collection(
            $this->tradeService->getLatestMatched($marketId)
        );
    }
}

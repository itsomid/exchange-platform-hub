<?php

namespace App\Http\Controllers\V1\Spot;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\OTC\MarketCollection;
use App\Services\OTC\OTCService;

class MarketController extends Controller
{
    public function __construct(private readonly OTCService $OTCService) {}

    /**
     * @OA\Get(
     *     path="/api/v1/spot/markets",
     *     summary="List SPOT Markets",
     *     description="Retrieve a list of Spot markets with their details such as base currency, quote currency, and trade limits.",
     *     tags={"Spot Orders"},
     *
     *     @OA\Response(
     *         response=200,
     *         description="List of OTC markets retrieved successfully.",
     *
     *         @OA\JsonContent(
     *             type="array",
     *
     *             @OA\Items(ref="#/components/schemas/Market")
     *         )
     *     ),
     * )
     */
    public function lists(): MarketCollection
    {
        return new MarketCollection($this->OTCService->markets());
    }
}

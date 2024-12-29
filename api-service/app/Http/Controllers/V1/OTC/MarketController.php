<?php

namespace App\Http\Controllers\V1\OTC;

use App\Http\Requests\V1\OTC\AvailableCoinRequest;
use App\Http\Resources\V1\OTC\AvailableCoinResource;
use App\Http\Resources\V1\OTC\MarketCollection;
use App\Services\OTC\OTCService;

class MarketController
{
    public function __construct(private readonly OTCService $OTCService) {}

    /**
     * @OA\Get(
     *     path="/api/v1/otc/markets",
     *     summary="List OTC Markets",
     *     description="Retrieve a list of OTC markets with their details such as base currency, quote currency, and trade limits.",
     *     tags={"OTC"},
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

    /**
     * @OA\Get(
     *     path="/api/v1/otc/bitexroom-available-coins",
     *     summary="Get Bitexroom Available Coins",
     *     description="Retrieve the available coins for trading in the Bitexroom market by market ID.",
     *     tags={"OTC"},
     *     @OA\Parameter(
     *         name="market_id",
     *         in="query",
     *         required=true,
     *         description="The ID of the market to retrieve available coins for.",
     *         @OA\Schema(
     *             type="integer",
     *             example=1
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="A list of available coins.",
     *         @OA\JsonContent(ref="#/components/schemas/AvailableCoin")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation failed for the request.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The market_id field is required."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="market_id",
     *                     type="array",
     *                     @OA\Items(type="string", example="The selected market_id is invalid.")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="An error occurred while retrieving available coins.")
     *         )
     *     )
     * )
     */

    public function bitexroomAvailableCoins(AvailableCoinRequest $request)
    {
        $validated = $request->validated();
        return new AvailableCoinResource(
            $this->OTCService->bitexroomAvailableCoins(
                $validated['market_id']
            )
        );
    }
}

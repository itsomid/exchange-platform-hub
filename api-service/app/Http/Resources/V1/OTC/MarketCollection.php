<?php

namespace App\Http\Resources\V1\OTC;

use App\Services\OTC\DTO\MarketResponseDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @OA\Schema(
 *     schema="Market",
 *     type="object",
 *     title="Market",
 *     description="Details of an OTC market.",
 *
 *     @OA\Property(
 *         property="base_currency",
 *         type="string",
 *         description="Base currency of the market.",
 *         example="BTC"
 *     ),
 *     @OA\Property(
 *         property="quote_currency",
 *         type="string",
 *         description="Quote currency of the market.",
 *         example="USDT"
 *     ),
 *     @OA\Property(
 *         property="is_active",
 *         type="boolean",
 *         description="Whether the market is active.",
 *         example=true
 *     ),
 *     @OA\Property(
 *         property="sell_price",
 *         type="number",
 *         format="float",
 *         description="Sell price in quote currency.",
 *         example=45000.50
 *     ),
 *     @OA\Property(
 *         property="buy_price",
 *         type="number",
 *         format="float",
 *         description="Buy price in quote currency.",
 *         example=44000.75
 *     ),
 *     @OA\Property(
 *         property="min_trade_amount",
 *         type="number",
 *         format="float",
 *         description="Minimum trade amount in base currency.",
 *         example=0.01
 *     ),
 *     @OA\Property(
 *         property="max_trade_amount",
 *         type="number",
 *         format="float",
 *         description="Maximum trade amount in base currency.",
 *         example=10.0
 *     ),
 *     @OA\Property(
 *          property="min_otc_amount",
 *          type="number",
 *          format="float",
 *          description="Minimum trade amount in base currency.",
 *          example=0.01
 *      ),
 *      @OA\Property(
 *          property="max_otc_amount",
 *          type="number",
 *          format="float",
 *          description="Maximum trade amount in base currency.",
 *          example=10.0
 *      )
 * )
 */
class MarketCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return $this->collection->map(fn (MarketResponseDTO $DTO) => [
            'market_id' => $DTO->getMarketId(),
            'precision' => $DTO->getPrecision(),
            'base_currency' => $DTO->getBaseCurrency(),
            'quote_currency' => $DTO->getQuoteCurrency(),
            'is_active' => $DTO->getIsActive(),
            'sell_price' => $DTO->getSellPrice(),
            'buy_price' => $DTO->getBuyPrice(),
            'min_trade_amount' => $DTO->getMinTradeAmount(),
            'max_trade_amount' => $DTO->getMaxTradeAmount(),
            'min_otc_amount' => $DTO->getMinOTCAmount(),
            'max_otc_amount' => $DTO->getMaxOTCAmount(),
        ])->toArray();
    }
}

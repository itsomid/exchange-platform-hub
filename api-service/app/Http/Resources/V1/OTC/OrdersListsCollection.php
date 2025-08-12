<?php

namespace App\Http\Resources\V1\OTC;

use App\Services\OTC\DTO\Order\OTCOrderListsResponseDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @OA\Schema(
 *     schema="OTCOrderHistoryResource",
 *     type="object",
 *     title="OTC Order History",
 *     description="Representation of an OTC order history entry.",
 *
 *     @OA\Property(
 *         property="created_at",
 *         type="string",
 *         format="date-time",
 *         description="The date and time when the order was created.",
 *         example="2025-01-10T12:34:56Z"
 *     ),
 *     @OA\Property(
 *         property="type",
 *         type="string",
 *         description="The type of the OTC order (e.g., buy or sell).",
 *         enum={"sell", "buy"},
 *         example="buy"
 *     ),
 *     @OA\Property(
 *         property="status",
 *         type="string",
 *         description="The current status of the OTC order.",
 *         enum={"success", "pending", "canceled"},
 *         example="success"
 *     ),
 *     @OA\Property(
 *         property="quantity",
 *         type="string",
 *         description="The quantity of the asset in the order.",
 *         example="1.2345"
 *     ),
 *     @OA\Property(
 *         property="price",
 *         type="string",
 *         description="The price of the asset in the order.",
 *         example="20000"
 *     ),
 *     @OA\Property(
 *         property="fee",
 *         type="string",
 *         description="The fee charged for the order.",
 *         example="0.001"
 *     ), *     @OA\Property(
 *         property="received_amount",
 *         type="string",
 *         description="The actual received amount.",
 *         example="0.001"
 *     ),
 *     @OA\Property(
 *         property="market_name",
 *         type="string",
 *         description="The market in which the OTC order was placed.",
 *         example="BTCUSDT"
 *     ),
 *          @OA\Property(
 *          property="base_currency",
 *          type="string",
 *          description="The base currency in which the OTC order was placed.",
 *          example="BTC"
 *      ),
 *      @OA\Property(
 *          property="quote_currency",
 *          type="string",
 *          description="The quote currency in which the OTC order was placed.",
 *          example="USDT"
 *      )
 * )
 */
class OrdersListsCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return $this->collection->map(fn(OTCOrderListsResponseDTO $responseDTO) => [
            'created_at' => $responseDTO->getCreatedAt(),
            'type' => $responseDTO->getType()->value,
            'status' => $responseDTO->getStatus()->value,
            'status_label' => __('enum.otc.status.' . $responseDTO->getStatus()->name),
            'quantity' => $responseDTO->getQuantity(),
            'price' => $responseDTO->getPrice(),
            'fee' => $responseDTO->getFee(),
            'received_amount' => $responseDTO->getReceivedAmount(),
            'market_name' => $responseDTO->getMarket(),
            'base_currency' => $responseDTO->getBaseCurrency(),
            'quote_currency' => $responseDTO->getQuoteCurrency(),
            'currency_logo' => $responseDTO->getCurrencyLogo(),
        ])->toArray();
    }
}

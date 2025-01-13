<?php

namespace App\Http\Controllers\V1\OTC;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\OTC\OrdersListsCollection;
use App\Services\OTC\DTO\Order\OTCOrderListsRequestDTO;
use App\Services\OTC\OTCOrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    public function __construct(private readonly OTCOrderService $service) {}

    /**
     * @OA\Get(
     *     path="/api/v1/otc/order-histories",
     *     summary="Get OTC Order Histories",
     *     description="Retrieve the OTC order history for the authenticated user.",
     *     tags={"OTC"},
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         required=false,
     *         description="Filter history by type (e.g., sell, buy)",
     *
     *         @OA\Schema(type="string", enum={"sell", "buy"}, example="sell")
     *     ),
     *
     *     @OA\Parameter(
     *         name="market",
     *         in="query",
     *         required=false,
     *         description="Filter history by market id (e.g., deposit, withdrawal).",
     *
     *         @OA\Schema(type="int", example="1")
     *     ),
     *
     *          @OA\Parameter(
     *          name="created_at",
     *          in="query",
     *          required=false,
     *          description="Filter history by created time",
     *
     *          @OA\Schema(type="string", example="2022-12-01,2022-12-15 12:30:00")
     *      ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="List of OTC order histories.",
     *
     *         @OA\JsonContent(
     *             type="array",
     *
     *             @OA\Items(ref="#/components/schemas/OTCOrderHistoryResource")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized, user is not authenticated.",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function lists(Request $request)
    {
        $listsDTO = $this->service->lists(
            resolve(OTCOrderListsRequestDTO::class)
                ->setFilterQueryString($request->only(['type', 'market', 'created_at']))
                ->setUserId(Auth::id())
        );

        return new OrdersListsCollection($listsDTO);
    }
}

<?php

namespace App\Http\Controllers\V1\OTC;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\OTC\OrdersListsCollection;
use App\Services\OTC\DTO\Order\OTCOrderListsRequestDTO;
use App\Services\OTC\OTCOrderService;
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
    public function lists()
    {
        $listsDTO = $this->service->lists(
            resolve(OTCOrderListsRequestDTO::class)
                ->setUserId(Auth::id())
        );

        return new OrdersListsCollection($listsDTO);
    }
}

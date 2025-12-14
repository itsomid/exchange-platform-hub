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
    public function __construct(private readonly OTCOrderService $OTCService) {}

 
    public function lists(Request $request)
    {
        $listsDTO = $this->OTCService->lists(
            resolve(OTCOrderListsRequestDTO::class)
                ->setFilterQueryString($request->only(['market', 'created_at']))
                ->setUserId(Auth::id())
                ->setPage((int)$request->query('page', 1))
                ->setLimit((int)$request->query('limit', 10))
        );

        return new OrdersListsCollection($listsDTO);
    }
}

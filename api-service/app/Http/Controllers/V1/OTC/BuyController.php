<?php

namespace App\Http\Controllers\V1\OTC;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\OTC\OTCBuyRequest;
use App\Services\OTC\DTO\OTCBuyRequestDTO;
use App\Services\OTC\OTCService;
use Illuminate\Support\Facades\Auth;

class BuyController extends Controller
{
    public function __construct(private readonly OTCService $OTCService) {}

    public function create(OTCBuyRequest $request)
    {
        $validateData = $request->validated();

        $this->OTCService->buy(
            resolve(OTCBuyRequestDTO::class)
                ->setMarketId($validateData['market_id'])
                ->setUserId(Auth::id())
                ->setQuantity($validateData['quantity'])
        );
    }
}

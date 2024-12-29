<?php

namespace App\Http\Controllers\V1\OTC;

use App\Http\Requests\V1\OTC\OTCSellRequest;
use App\Services\OTC\DTO\OTCSellRequestDTO;
use App\Services\OTC\OTCService;
use Illuminate\Support\Facades\Auth;

class SellController
{
    public function __construct(private readonly OTCService $OTCService) {}

    public function create(OTCSellRequest $request)
    {
        $validateData = $request->validated();

        $this->OTCService->sell(
            resolve(OTCSellRequestDTO::class)
                ->setMarketId($validateData['market_id'])
                ->setSellerUserId(Auth::id())
                ->setBuyerUserId(config('bitexroom.bitexroom_user_id'))
                ->setQuantity($validateData['quantity'])
        );

        return response([
            'message' => 'successfully',
        ]);
    }
}

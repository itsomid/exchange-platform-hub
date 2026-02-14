<?php

namespace App\Http\Controllers\V1\OTC;

use App\Events\OTCOrderCreated;
use App\Http\Requests\V1\OTC\OTCSellRequest;
use App\Models\Setting;
use App\Services\OTC\DTO\OTCSellRequestDTO;
use App\Services\OTC\OTCService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class SellController
{
    public function __construct(private readonly OTCService $OTCService) {}


    public function create(OTCSellRequest $request)
    {
        // Check if OTC trading is enabled
        if (!Setting::isEnabled('otc_trading_enabled')) {
            return response([
                'message' => __('otc.trading_disabled'),
            ], Response::HTTP_FORBIDDEN);
        }

        $validateData = $request->validated();
        $lock = Cache::lock('order-sell:'.$validateData['market_id'].Auth::id(), 40);

        if ($lock->get()) {
            try {
                $responseDTO = $this->OTCService->sell(
                    resolve(OTCSellRequestDTO::class)
                        ->setMarketId($validateData['market_id'])
                        ->setSellerUserId(Auth::id())
                        ->setBuyerUserId(config('bitexroom.user_id'))
                        ->setQuantity($validateData['quantity'])
                );

                event(new OTCOrderCreated($responseDTO->getOtcOrderModel(), 'sell'));

                return response([
                    'message' => __('otc.sell_order_submitted'),
                ]);
            } catch (Throwable $exception) {
                $lock->release();
                throw $exception;
            } finally {
                $lock->release();
            }
        }

        return response([
            'message' => __('auth.too_many_attempts'),
        ], Response::HTTP_TOO_MANY_REQUESTS);
    }
}

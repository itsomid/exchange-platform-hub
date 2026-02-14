<?php

namespace App\Http\Controllers\V1\OTC;

use App\Events\OTCOrderCreated;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\OTC\OTCBuyRequest;
use App\Models\Setting;
use App\Services\OTC\DTO\OTCBuyRequestDTO;
use App\Services\OTC\OTCService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class BuyController extends Controller
{
    public function __construct(private readonly OTCService $OTCService) {}

    public function create(OTCBuyRequest $request)
    {
        // Check if OTC trading is enabled
        if (!Setting::isEnabled('otc_trading_enabled')) {
            return response([
                'message' => __('otc.trading_disabled'),
            ], Response::HTTP_FORBIDDEN);
        }

        $validateData = $request->validated();

        $lock = Cache::lock('order-buy:'.$validateData['market_id'].Auth::id(), 40);

        if ($lock->get()) {
            try {
                $responseDTO = $this->OTCService->buy(
                    resolve(OTCBuyRequestDTO::class)
                        ->setMarketId($validateData['market_id'])
                        ->setBuyerUserId(Auth::id())
                        ->setSellerUserId(config('bitexroom.user_id'))
                        ->setQuantity($validateData['quantity'])
                );

                event(new OTCOrderCreated($responseDTO->getOtcOrderModel(), 'buy'));

                return response([
                    'message' => __('otc.buy_order_submitted'),
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

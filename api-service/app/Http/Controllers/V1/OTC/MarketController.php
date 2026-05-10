<?php

namespace App\Http\Controllers\V1\OTC;

use App\Http\Requests\V1\OTC\AvailableBalanceRequest;
use App\Http\Resources\V1\OTC\AvailableCoinResource;
use App\Http\Resources\V1\OTC\MarketCollection;
use App\Services\OTC\OTCService;

class MarketController
{
    public function __construct(private readonly OTCService $OTCService) {}


    public function lists(): MarketCollection
    {
        return new MarketCollection($this->OTCService->markets());
    }

    public function prices(int $marketId)
    {
        $data = $this->OTCService->getPrices($marketId);
        if (isset($data['status']) && $data['status'] === 404) {
            return response($data, 404);
        }
        return response($data);
    }


    public function bitexroomAvailableBalance(AvailableBalanceRequest $request)
    {
        $validated = $request->validated();

        return new AvailableCoinResource(
            $this->OTCService->bitexroomAvailableBalance(
                $validated['market_id']
            )
        );
    }

    public function topTraded()
    {
        $topPairs = \App\Models\OTCOrder::query()
            ->with(['market.currency', 'market.quoteCurrency'])
            ->where('status', \App\Enums\OTCOrderStatusEnum::SUCCESS)
            ->whereBetween('created_at', [now()->subDays(30), now()])
            ->whereNotNull('market_id')
            ->selectRaw('market_id, COUNT(*) as trades_count, SUM(quantity * price) as volume')
            ->groupBy('market_id')
            ->orderByDesc('volume')
            ->take(5)
            ->get()
            ->map(function ($order) {
                if (!$order->market) {
                    return null;
                }
                $prices = $this->OTCService->getPrices($order->market_id);
                $buyPrice = (isset($prices['status']) && $prices['status'] === 404) ? null : $prices['buy_price'];
                $sellPrice = (isset($prices['status']) && $prices['status'] === 404) ? null : $prices['sell_price'];
                return [
                    'market_id' => $order->market->id,
                    'pair' => $order->market->base_currency . '/' . $order->market->quote_currency,
                    'currency_logo' => $order->market->currency?->logo
                        ? config('bitexroom.currency_logo_base_url') . '/' . $order->market->currency->logo
                        : null,
                    'volume' => formatNumberTrimZeros($order->volume),
                    'buy_price' => $buyPrice !== null ? formatNumberTrimZeros($buyPrice) : null,
                    'sell_price' => $sellPrice !== null ? formatNumberTrimZeros($sellPrice) : null,
                ];
            })
            ->filter()
            ->values();

        return response(['data' => $topPairs]);
    }
}

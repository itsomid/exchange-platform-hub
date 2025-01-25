<?php

namespace App\Listeners;

use App\Events\OTCOrderCreated;
use App\Models\ExchangeTransaction;
use App\Services\Exchanges\Asset\AssetFactory;
use App\Services\Exchanges\Asset\DTO\BuyDTORequest;

class ChargeCoinex
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(OTCOrderCreated $event): void
    {
        $asset = AssetFactory::make('coinex');

        $otcOrder = $event->OTCOrder;
        $market = $event->OTCOrder->market;

        $response = $asset->placeOrder(
            resolve(BuyDTORequest::class)
                ->setSide('buy')
                ->setMarket($market->base_currency.$market->quote_currency)
                ->setMarketType('SPOT')
                ->setQuantity($otcOrder->received_amount)
                ->setOrderType('market')
        );

        ExchangeTransaction::query()
            ->create([
                'order_id' => $response->getOrderId(),
                'market' => $response->getMarket(),
                'amount' => $response->getAmount(),
                'fee' => $response->getDiscountFee(),
                'side' => 'buy',
                'response' => $response->getResponseBody(),
            ]);
    }
}

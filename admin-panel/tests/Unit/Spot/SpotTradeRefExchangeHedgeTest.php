<?php

namespace Tests\Unit\Spot;

use App\Enums\SpotOrderSideEnum;
use App\Enums\SpotOrderSourceEnum;
use App\Models\SpotOrder;
use App\Models\SpotTrade;
use Tests\TestCase;

class SpotTradeRefExchangeHedgeTest extends TestCase
{
    public function test_user_buy_from_bot_is_a_reference_buy(): void
    {
        $trade = $this->trade(
            makerSource: SpotOrderSourceEnum::BOT,
            makerSide: SpotOrderSideEnum::SELL,
            takerSource: SpotOrderSourceEnum::USER,
            takerSide: SpotOrderSideEnum::BUY,
        );

        $this->assertSame('buy', $trade->refExchangeHedgeSide());
    }

    public function test_user_sell_to_bot_stays_a_reference_sell(): void
    {
        $trade = $this->trade(
            makerSource: SpotOrderSourceEnum::USER,
            makerSide: SpotOrderSideEnum::SELL,
            takerSource: SpotOrderSourceEnum::BOT,
            takerSide: SpotOrderSideEnum::BUY,
        );

        $this->assertSame('sell', $trade->refExchangeHedgeSide());
    }

    public function test_bot_to_bot_trade_keeps_the_sell_hedge(): void
    {
        $trade = $this->trade(
            makerSource: SpotOrderSourceEnum::BOT,
            makerSide: SpotOrderSideEnum::SELL,
            takerSource: SpotOrderSourceEnum::BOT,
            takerSide: SpotOrderSideEnum::BUY,
        );

        $this->assertSame('sell', $trade->refExchangeHedgeSide());
    }

    private function trade(
        SpotOrderSourceEnum $makerSource,
        SpotOrderSideEnum $makerSide,
        SpotOrderSourceEnum $takerSource,
        SpotOrderSideEnum $takerSide,
    ): SpotTrade {
        $trade = new SpotTrade();
        $trade->setRelation('makerOrder', $this->order($makerSource, $makerSide));
        $trade->setRelation('takerOrder', $this->order($takerSource, $takerSide));

        return $trade;
    }

    private function order(SpotOrderSourceEnum $source, SpotOrderSideEnum $side): SpotOrder
    {
        $order = new SpotOrder();
        $order->source = $source;
        $order->side = $side;

        return $order;
    }
}

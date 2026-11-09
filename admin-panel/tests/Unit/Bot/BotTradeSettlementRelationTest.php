<?php

namespace Tests\Unit\Bot;

use App\Models\Bot\BotTradeSettlement;
use Tests\TestCase;

class BotTradeSettlementRelationTest extends TestCase
{
    public function test_relations_use_the_real_foreign_keys(): void
    {
        $settlement = new BotTradeSettlement;

        $this->assertSame('bot_buy_execution_id', $settlement->buyExecution()->getForeignKeyName());
        $this->assertSame('bot_sell_order_id', $settlement->sellOrder()->getForeignKeyName());
    }
}

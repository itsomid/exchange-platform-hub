<?php

namespace Tests\Unit\Bot;

use App\Models\Bot\BotBuyExecution;
use App\Models\Bot\BotOrder;
use App\Models\Bot\BotSellOrder;
use App\Models\Bot\BotTradeSettlement;
use App\Services\Bot\BotCanceledReason;
use Illuminate\Database\Eloquent\Collection;
use Tests\TestCase;

class BotCanceledReasonTest extends TestCase
{
    public function test_returns_null_when_sell_is_not_canceled(): void
    {
        $sell = $this->sell(['status' => 'OPEN']);
        $sell->setRelation('settlement', null);

        $this->assertNull(BotCanceledReason::forSellOrder(
            $sell,
            $this->execution(['status' => 'BOUGHT']),
            $this->order(['status' => 'FILLED']),
        ));
    }

    public function test_explains_manual_order_cancel_with_market_settlement(): void
    {
        $sell = $this->sell(['status' => 'CANCELED', 'exchange_order_id' => 'ex-1']);
        $sell->setRelation('settlement', $this->settlement([
            'cancel_fee'     => '0',
            'gross_revenue'  => '12.50000000',
            'net_pnl'        => '-0.40000000',
        ]));

        $result = BotCanceledReason::forSellOrder(
            $sell,
            $this->execution(['status' => 'BOUGHT']),
            $this->order(['status' => 'CANCELED', 'completed_at' => '2026-08-06 00:15:00']),
        );

        $this->assertSame('order_canceled', $result['kind']);
        $this->assertSame('لغو سفارش ربات پس از خرید', $result['title']);
        $this->assertStringContainsString('خرید این ارز انجام شده بود', $result['lines'][0]);
        $this->assertStringContainsString('2026-08-06 00:15', implode("\n", $result['lines']));
        $this->assertStringContainsString('gross', implode("\n", $result['lines']));
    }

    public function test_explains_legacy_cancel_fee_settlement(): void
    {
        $sell = $this->sell(['status' => 'CANCELED']);
        $sell->setRelation('settlement', $this->settlement([
            'cancel_fee'    => '0.50000000',
            'gross_revenue' => '0',
            'net_pnl'       => '-0.50000000',
        ]));

        $result = BotCanceledReason::forSellOrder(
            $sell,
            $this->execution(['status' => 'BOUGHT']),
            $this->order(['status' => 'CANCELED']),
        );

        $this->assertSame('cancel_fee', $result['kind']);
        $this->assertStringContainsString('کارمزد لغو', implode("\n", $result['lines']));
    }

    public function test_explains_unplaced_tier_after_exchange_place_failure(): void
    {
        $sell = $this->sell(['status' => 'CANCELED', 'exchange_order_id' => null]);
        $sell->setRelation('settlement', null);

        $result = BotCanceledReason::forSellOrder(
            $sell,
            $this->execution([
                'status'         => 'FAILED',
                'failure_reason' => 'coinex.sell.place_failed market=AAAUSDT code=3103 msg=Balance insufficient | auto-liquidated',
            ]),
            $this->order(['status' => 'FAILED']),
        );

        $this->assertSame('place_never', $result['kind']);
        $this->assertStringContainsString('هرگز روی صرافی مرجع ثبت نشد', implode("\n", $result['lines']));
        $this->assertStringContainsString('AAAUSDT', implode("\n", $result['lines']));
        $this->assertStringContainsString('3103', implode("\n", $result['lines']));
        $this->assertStringContainsString('Balance insufficient', implode("\n", $result['lines']));
        $this->assertStringContainsString('نقد شد', implode("\n", $result['lines']));
    }

    public function test_explains_rollback_of_already_placed_tier(): void
    {
        $sell = $this->sell(['status' => 'CANCELED', 'exchange_order_id' => 'ex-9']);
        $sell->setRelation('settlement', null);

        $result = BotCanceledReason::forSellOrder(
            $sell,
            $this->execution([
                'status'         => 'FAILED',
                'failure_reason' => 'coinex.sell.place_failed market=BBBUSDT code=n/a msg=timeout',
            ]),
            $this->order(['status' => 'FAILED']),
        );

        $this->assertSame('place_rollback', $result['kind']);
        $this->assertStringContainsString('ثبت پله بعدی شکست خورد', implode("\n", $result['lines']));
    }

    public function test_explains_exchange_sync_cancel_without_settlement(): void
    {
        $sell = $this->sell(['status' => 'CANCELED', 'exchange_order_id' => 'ex-77']);
        $sell->setRelation('settlement', null);

        $result = BotCanceledReason::forSellOrder(
            $sell,
            $this->execution(['status' => 'BOUGHT']),
            $this->order(['status' => 'PARTIALLY_FILLED']),
        );

        $this->assertSame('exchange_sync', $result['kind']);
        $this->assertStringContainsString('همگام‌سازی', implode("\n", $result['lines']));
        $this->assertStringContainsString('قفل', implode("\n", $result['lines']));
    }

    public function test_explains_parent_bot_order_cancel(): void
    {
        $bought = $this->execution(['status' => 'BOUGHT']);
        $canceledSell = $this->sell(['status' => 'CANCELED']);
        $filledSell = $this->sell(['status' => 'FILLED']);
        $bought->setRelation('sellOrders', new Collection([$canceledSell, $filledSell]));

        $failed = $this->execution(['status' => 'FAILED']);
        $failed->setRelation('sellOrders', new Collection());

        $order = $this->order(['status' => 'CANCELED', 'completed_at' => '2026-08-06 00:15:00']);
        $order->setRelation('buyExecutions', new Collection([$bought, $failed]));

        $result = BotCanceledReason::forBotOrder($order);

        $this->assertSame('manual_cancel', $result['kind']);
        $this->assertStringContainsString('لغو شده است', implode("\n", $result['lines']));
        $this->assertStringContainsString('اجراهای خرید موفق: 1', implode("\n", $result['lines']));
        $this->assertStringContainsString('پله‌های فروش کنسل‌شده: 1', implode("\n", $result['lines']));
        $this->assertStringContainsString('FAILED', implode("\n", $result['lines']));
    }

    public function test_parent_order_returns_null_when_not_canceled(): void
    {
        $this->assertNull(BotCanceledReason::forBotOrder($this->order(['status' => 'FILLED'])));
    }

    public function test_prefers_stored_admin_cancel_reason(): void
    {
        $sell = $this->sell([
            'status'         => 'CANCELED',
            'cancel_reason'  => BotSellOrder::CANCEL_ADMIN,
            'exchange_order_id' => 'ex-1',
        ]);
        $sell->setRelation('settlement', $this->settlement([
            'cancel_fee'    => '0',
            'gross_revenue' => '12.50000000',
            'net_pnl'       => '-0.40000000',
        ]));

        $result = BotCanceledReason::forSellOrder(
            $sell,
            $this->execution(['status' => 'CLOSED']),
            $this->order(['status' => 'CANCELED', 'cancel_source' => BotOrder::CANCEL_SOURCE_ADMIN]),
        );

        $this->assertSame(BotSellOrder::CANCEL_ADMIN, $result['kind']);
        $this->assertStringContainsString('از پنل ادمین', implode("\n", $result['lines']));
    }

    public function test_explains_closed_buy_execution(): void
    {
        $result = BotCanceledReason::forBuyExecution(
            $this->execution(['status' => 'CLOSED']),
            $this->order(['status' => 'CANCELED', 'cancel_source' => BotOrder::CANCEL_SOURCE_USER]),
        );

        $this->assertSame('closed', $result['kind']);
        $this->assertStringContainsString('توسط کاربر', implode("\n", $result['lines']));
    }

    public function test_parent_order_uses_stored_cancel_source(): void
    {
        $closed = $this->execution(['status' => 'CLOSED']);
        $closed->setRelation('sellOrders', new Collection([
            $this->sell(['status' => 'CANCELED', 'cancel_reason' => BotSellOrder::CANCEL_ADMIN]),
        ]));

        $order = $this->order([
            'status'        => 'CANCELED',
            'cancel_source' => BotOrder::CANCEL_SOURCE_ADMIN,
            'completed_at'  => '2026-08-06 00:15:00',
        ]);
        $order->setRelation('buyExecutions', new Collection([$closed]));

        $result = BotCanceledReason::forBotOrder($order);

        $this->assertStringContainsString('از پنل ادمین', implode("\n", $result['lines']));
        $this->assertStringContainsString('اجراهای بسته‌شده پس از لغو: 1', implode("\n", $result['lines']));
    }

    private function sell(array $attrs): BotSellOrder
    {
        $model = new BotSellOrder();
        $model->setRawAttributes($attrs + ['status' => 'CANCELED'], true);

        return $model;
    }

    private function execution(array $attrs): BotBuyExecution
    {
        $model = new BotBuyExecution();
        $model->setRawAttributes($attrs + ['status' => 'BOUGHT'], true);

        return $model;
    }

    private function order(array $attrs): BotOrder
    {
        $model = new BotOrder();
        $model->setRawAttributes($attrs + ['status' => 'CANCELED'], true);

        return $model;
    }

    private function settlement(array $attrs): BotTradeSettlement
    {
        $model = new BotTradeSettlement();
        $model->setRawAttributes($attrs, true);

        return $model;
    }
}

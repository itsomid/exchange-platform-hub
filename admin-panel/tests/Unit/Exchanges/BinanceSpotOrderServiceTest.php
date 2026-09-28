<?php

namespace Tests\Unit\Exchanges;

use App\Exceptions\Exchange\RefExchangeRequestException;
use App\Services\Exchanges\Asset\Binance\BinanceSpotOrderService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BinanceSpotOrderServiceTest extends TestCase
{
    public function test_finished_orders_exclude_open_ones_and_are_newest_first(): void
    {
        Http::fake([
            '*/api/v3/time*' => Http::response(['serverTime' => 1700000000000], 200),
            '*/api/v3/allOrders*' => Http::response([
                $this->order(1, 'FILLED', 1000),
                $this->order(2, 'NEW', 2000),
                $this->order(3, 'CANCELED', 3000),
                $this->order(4, 'PARTIALLY_FILLED', 4000),
            ], 200),
        ]);

        $result = (new BinanceSpotOrderService())->getFinishedOrders('ADAUSDT');

        $this->assertSame([3, 1], array_column($result['data'], 'order_id'));
        $this->assertSame(['canceled', 'filled'], array_column($result['data'], 'status'));
        $this->assertSame(2, $result['pagination']['total']);
        $this->assertFalse($result['pagination']['has_next']);
    }

    public function test_find_order_by_id_skips_markets_where_order_is_missing(): void
    {
        Http::fake([
            '*/api/v3/time*' => Http::response(['serverTime' => 1700000000000], 200),
            '*/api/v3/order*' => function ($request) {
                if (str_contains($request->url(), 'symbol=BTCUSDT')) {
                    return Http::response(['code' => -2013, 'msg' => 'Order does not exist.'], 400);
                }

                return Http::response($this->order(555, 'FILLED', 1000), 200);
            },
        ]);

        $found = (new BinanceSpotOrderService())->findOrderById(555, null, ['BTCUSDT', 'ADAUSDT']);

        $this->assertSame('ADAUSDT', $found['market']);
        $this->assertSame(555, $found['order']['order_id']);
    }

    public function test_find_order_by_id_throws_when_not_found_anywhere(): void
    {
        Http::fake([
            '*/api/v3/time*' => Http::response(['serverTime' => 1700000000000], 200),
            '*/api/v3/order*' => Http::response(['code' => -2013, 'msg' => 'Order does not exist.'], 400),
        ]);

        $this->expectException(RefExchangeRequestException::class);

        (new BinanceSpotOrderService())->findOrderById(555, 'ADAUSDT');
    }

    public function test_market_balances_include_free_and_locked(): void
    {
        Http::fake([
            '*/api/v3/time*' => Http::response(['serverTime' => 1700000000000], 200),
            '*/api/v3/account*' => Http::response([
                'balances' => [
                    ['asset' => 'ADA', 'free' => '10.50000000', 'locked' => '2.00000000'],
                    ['asset' => 'USDT', 'free' => '100.00000000', 'locked' => '0.00000000'],
                ],
            ], 200),
        ]);

        $balances = (new BinanceSpotOrderService())->getMarketBalances('ada');

        $this->assertSame('10.5', $balances['base']['available']);
        $this->assertSame('2', $balances['base']['frozen']);
        $this->assertSame('12.50000000', $balances['base']['total']);
        $this->assertSame('100', $balances['usdt']['available']);
    }

    public function test_cancel_order_sends_delete_and_surfaces_errors(): void
    {
        Http::fake([
            '*/api/v3/time*' => Http::response(['serverTime' => 1700000000000], 200),
            '*/api/v3/order*' => Http::response(['code' => -2011, 'msg' => 'Unknown order sent.'], 400),
        ]);

        try {
            (new BinanceSpotOrderService())->cancelOrder('ADAUSDT', 555);
            $this->fail('Expected RefExchangeRequestException');
        } catch (RefExchangeRequestException $e) {
            $this->assertSame('Unknown order sent.', $e->getMessage());
        }

        Http::assertSent(fn ($request) => $request->method() === 'DELETE'
            && str_contains($request->url(), '/api/v3/order')
            && str_contains($request->url(), 'orderId=555'));
    }

    private function order(int $id, string $status, int $time): array
    {
        return [
            'symbol' => 'ADAUSDT',
            'orderId' => $id,
            'price' => '0.5',
            'origQty' => '100',
            'executedQty' => '0',
            'cummulativeQuoteQty' => '0',
            'status' => $status,
            'type' => 'LIMIT',
            'side' => 'BUY',
            'time' => $time,
            'updateTime' => $time,
        ];
    }
}

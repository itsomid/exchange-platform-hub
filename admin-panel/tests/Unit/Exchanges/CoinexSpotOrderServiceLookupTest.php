<?php

namespace Tests\Unit\Exchanges;

use App\Exceptions\Exchange\CantResolveCoinexException;
use App\Services\Exchanges\Asset\Coinex\CoinexSpotOrderService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CoinexSpotOrderServiceLookupTest extends TestCase
{
    public function test_get_order_status_returns_order_payload(): void
    {
        Http::fake([
            'api.coinex.com/v2/spot/order-status*' => Http::response($this->orderStatusResponse(), 200),
        ]);

        $order = (new CoinexSpotOrderService())->getOrderStatus('ADAUSDT', 13400);

        $this->assertSame(13400, $order['order_id']);
        $this->assertSame('buy', $order['side']);
        $this->assertSame('part_deal', $order['status']);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/v2/spot/order-status')
                && str_contains($request->url(), 'market=ADAUSDT')
                && str_contains($request->url(), 'order_id=13400');
        });
    }

    public function test_get_order_deals_returns_fills(): void
    {
        Http::fake([
            'api.coinex.com/v2/spot/order-deals*' => Http::response($this->orderDealsResponse(), 200),
        ]);

        $result = (new CoinexSpotOrderService())->getOrderDeals('ADAUSDT', 13400);

        $this->assertCount(1, $result['data']);
        $this->assertSame(3514376759, $result['data'][0]['deal_id']);
        $this->assertFalse($result['pagination']['has_next']);
    }

    public function test_find_order_by_id_scans_markets_until_match(): void
    {
        Http::fake([
            'api.coinex.com/v2/spot/order-status*' => function ($request) {
                if (str_contains($request->url(), 'market=BTCUSDT')) {
                    return Http::response(['code' => 4004, 'message' => 'not found', 'data' => null], 200);
                }

                return Http::response($this->orderStatusResponse(), 200);
            },
        ]);

        $found = (new CoinexSpotOrderService())->findOrderById(
            '173390586784',
            null,
            ['BTCUSDT', 'ADAUSDT']
        );

        $this->assertSame('ADAUSDT', $found['market']);
        $this->assertSame(13400, $found['order']['order_id']);
    }

    public function test_get_order_status_throws_on_coinex_error(): void
    {
        Http::fake([
            'api.coinex.com/v2/spot/order-status*' => Http::response([
                'code' => 4004,
                'message' => 'Invalid order_id',
                'data' => null,
            ], 200),
        ]);

        $this->expectException(CantResolveCoinexException::class);

        (new CoinexSpotOrderService())->getOrderStatus('ADAUSDT', 1);
    }

    private function orderStatusResponse(): array
    {
        return [
            'code' => 0,
            'message' => 'OK',
            'data' => [
                'order_id' => 13400,
                'market' => 'ADAUSDT',
                'market_type' => 'SPOT',
                'ccy' => 'ADA',
                'side' => 'buy',
                'type' => 'limit',
                'amount' => '100',
                'price' => '0.5',
                'unfilled_amount' => '40',
                'filled_amount' => '60',
                'filled_value' => '30',
                'client_id' => 'client_1',
                'base_fee' => '0',
                'quote_fee' => '0.009',
                'discount_fee' => '0',
                'maker_fee_rate' => '0',
                'taker_fee_rate' => '0.0003',
                'last_fill_amount' => '60',
                'last_fill_price' => '0.5',
                'created_at' => 1691482451000,
                'updated_at' => 1691482451000,
                'status' => 'part_deal',
            ],
        ];
    }

    private function orderDealsResponse(): array
    {
        return [
            'code' => 0,
            'message' => 'OK',
            'data' => [
                [
                    'deal_id' => 3514376759,
                    'created_at' => 1689152421692,
                    'market' => 'ADAUSDT',
                    'side' => 'buy',
                    'order_id' => 13400,
                    'price' => '0.5',
                    'amount' => '60',
                    'role' => 'taker',
                    'fee' => '0.009',
                    'fee_ccy' => 'USDT',
                ],
            ],
            'pagination' => [
                'has_next' => false,
            ],
        ];
    }
}

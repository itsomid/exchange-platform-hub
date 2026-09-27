<?php

namespace Tests\Feature\Exchange;

use App\Models\Admin;
use App\Models\Currency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class RefExchangeSpotOrderTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    private Currency $currency;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(
            ['name' => 'ref-exchanges', 'guard_name' => 'admin'],
            ['persian_name' => 'مدیریت صرافی های مرجع']
        );

        $this->admin = Admin::factory()->create(['is_active' => true])->fresh();
        $this->admin->givePermissionTo('ref-exchanges');

        $this->currency = Currency::factory()->create([
            'symbol' => 'ADA',
            'name' => 'Cardano',
            'persian_name' => 'کاردانو',
            'is_active' => true,
        ]);

        foreach (['coinex' => 'CoinEx', 'binance' => 'Binance', 'mexc' => 'MEXC'] as $slug => $name) {
            DB::table('exchanges')->updateOrInsert(
                ['slug' => $slug],
                ['name' => $name, 'is_active' => false, 'priority' => 1, 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }

    public function test_guest_cannot_lookup_order(): void
    {
        $this->getJson(route('admin.ref-exchange.spot-orders.lookup', [
            'exchange' => 'coinex',
            'currency_id' => $this->currency->id,
            'order_id' => 13400,
        ]))->assertUnauthorized();
    }

    public function test_admin_without_permission_cannot_lookup_order(): void
    {
        $other = Admin::factory()->create(['is_active' => true]);

        $this->actingAs($other, 'admin')
            ->getJson(route('admin.ref-exchange.spot-orders.lookup', [
                'exchange' => 'coinex',
                'currency_id' => $this->currency->id,
                'order_id' => 13400,
            ]))
            ->assertForbidden();
    }

    public function test_index_asks_for_reference_exchange_first(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.ref-exchange.spot-orders.index'))
            ->assertOk()
            ->assertSee('انتخاب صرافی مرجع')
            ->assertSee('CoinEx')
            ->assertSee('Binance')
            ->assertDontSee('MEXC')
            ->assertDontSee('lookup_order_id', false);
    }

    public function test_selected_exchange_is_remembered_between_visits(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.ref-exchange.spot-orders.index', ['exchange' => 'binance']))
            ->assertOk()
            ->assertSee('جستجو بر اساس شناسه سفارش Binance')
            ->assertSee('lookup_order_id', false);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.ref-exchange.spot-orders.index'))
            ->assertOk()
            ->assertSee('جستجو بر اساس شناسه سفارش Binance');

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.ref-exchange.spot-orders.index', ['exchange' => 'coinex']))
            ->assertOk()
            ->assertSee('جستجو بر اساس شناسه سفارش CoinEx');
    }

    public function test_unsupported_exchange_is_not_selectable(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.ref-exchange.spot-orders.index', ['exchange' => 'mexc']))
            ->assertOk()
            ->assertSee('انتخاب صرافی مرجع');
    }

    public function test_lookup_requires_order_id_and_exchange(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->getJson(route('admin.ref-exchange.spot-orders.lookup'))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['order_id', 'exchange']);
    }

    public function test_lookup_rejects_unsupported_exchange(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->getJson(route('admin.ref-exchange.spot-orders.lookup', [
                'exchange' => 'mexc',
                'order_id' => 13400,
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['exchange']);
    }

    public function test_lookup_rejects_invalid_currency(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->getJson(route('admin.ref-exchange.spot-orders.lookup', [
                'exchange' => 'coinex',
                'currency_id' => 999999,
                'order_id' => 13400,
            ]))
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'کوین انتخاب‌شده معتبر نیست.',
            ]);
    }

    public function test_lookup_returns_order_and_deals(): void
    {
        Http::fake([
            'api.coinex.com/v2/spot/order-status*' => Http::response($this->orderStatusResponse(), 200),
            'api.coinex.com/v2/spot/order-deals*' => Http::response($this->orderDealsResponse(), 200),
        ]);

        $this->actingAs($this->admin, 'admin')
            ->getJson(route('admin.ref-exchange.spot-orders.lookup', [
                'exchange' => 'coinex',
                'currency_id' => $this->currency->id,
                'order_id' => 13400,
            ]))
            ->assertOk()
            ->assertJson([
                'success' => true,
                'market' => 'ADAUSDT',
                'order' => [
                    'order_id' => 13400,
                    'side' => 'buy',
                    'status' => 'part_deal',
                ],
                'deals_error' => null,
            ])
            ->assertJsonPath('deals.0.deal_id', 3514376759);
    }

    public function test_lookup_returns_binance_order_in_common_shape(): void
    {
        Http::fake([
            '*/api/v3/time*' => Http::response(['serverTime' => 1700000000000], 200),
            '*/api/v3/order*' => Http::response([
                'symbol' => 'ADAUSDT',
                'orderId' => 555,
                'clientOrderId' => 'abc',
                'price' => '0.50000000',
                'origQty' => '100.00000000',
                'executedQty' => '60.00000000',
                'cummulativeQuoteQty' => '30.00000000',
                'status' => 'PARTIALLY_FILLED',
                'type' => 'LIMIT',
                'side' => 'BUY',
                'time' => 1700000000000,
                'updateTime' => 1700000001000,
            ], 200),
            '*/api/v3/myTrades*' => Http::response([[
                'symbol' => 'ADAUSDT',
                'id' => 9001,
                'orderId' => 555,
                'price' => '0.50000000',
                'qty' => '60.00000000',
                'commission' => '0.06000000',
                'commissionAsset' => 'ADA',
                'time' => 1700000001000,
                'isBuyer' => true,
                'isMaker' => false,
            ]], 200),
        ]);

        $this->actingAs($this->admin, 'admin')
            ->getJson(route('admin.ref-exchange.spot-orders.lookup', [
                'exchange' => 'binance',
                'currency_id' => $this->currency->id,
                'order_id' => 555,
            ]))
            ->assertOk()
            ->assertJson([
                'success' => true,
                'market' => 'ADAUSDT',
                'order' => [
                    'order_id' => 555,
                    'side' => 'buy',
                    'type' => 'limit',
                    'amount' => '100',
                    'filled_amount' => '60',
                    'unfilled_amount' => '40',
                    'status' => 'part_deal',
                ],
                'deals' => [[
                    'deal_id' => 9001,
                    'side' => 'buy',
                    'role' => 'taker',
                    'fee' => '0.06',
                    'fee_ccy' => 'ADA',
                ]],
            ]);
    }

    public function test_lookup_still_returns_order_when_deals_fail(): void
    {
        Http::fake([
            'api.coinex.com/v2/spot/order-status*' => Http::response($this->orderStatusResponse(), 200),
            'api.coinex.com/v2/spot/order-deals*' => Http::response([
                'code' => 4004,
                'message' => 'deals unavailable',
                'data' => null,
            ], 200),
        ]);

        $this->actingAs($this->admin, 'admin')
            ->getJson(route('admin.ref-exchange.spot-orders.lookup', [
                'exchange' => 'coinex',
                'currency_id' => $this->currency->id,
                'order_id' => 13400,
            ]))
            ->assertOk()
            ->assertJson([
                'success' => true,
                'order' => [
                    'order_id' => 13400,
                ],
                'deals' => [],
            ])
            ->assertJsonPath('deals_error', fn ($value) => is_string($value) && $value !== '');
    }

    public function test_lookup_can_find_order_without_currency_by_scanning_markets(): void
    {
        Http::fake([
            'api.coinex.com/v2/spot/order-status*' => Http::response($this->orderStatusResponse(), 200),
            'api.coinex.com/v2/spot/order-deals*' => Http::response($this->orderDealsResponse(), 200),
        ]);

        $this->actingAs($this->admin, 'admin')
            ->getJson(route('admin.ref-exchange.spot-orders.lookup', [
                'exchange' => 'coinex',
                'order_id' => '173390586784',
            ]))
            ->assertOk()
            ->assertJson([
                'success' => true,
                'market' => 'ADAUSDT',
            ]);
    }

    public function test_lookup_surfaces_coinex_order_error(): void
    {
        Http::fake([
            'api.coinex.com/v2/spot/order-status*' => Http::response([
                'code' => 4004,
                'message' => 'Order not found',
                'data' => null,
            ], 200),
        ]);

        $this->actingAs($this->admin, 'admin')
            ->getJson(route('admin.ref-exchange.spot-orders.lookup', [
                'exchange' => 'coinex',
                'currency_id' => $this->currency->id,
                'order_id' => 13400,
            ]))
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
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

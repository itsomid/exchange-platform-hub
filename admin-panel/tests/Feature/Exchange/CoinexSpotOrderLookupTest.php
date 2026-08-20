<?php

namespace Tests\Feature\Exchange;

use App\Models\Admin;
use App\Models\Currency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CoinexSpotOrderLookupTest extends TestCase
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

        $this->admin = Admin::factory()->create();
        $this->admin->givePermissionTo('ref-exchanges');

        $this->currency = Currency::factory()->create([
            'symbol' => 'ADA',
            'name' => 'Cardano',
            'persian_name' => 'کاردانو',
            'is_active' => true,
        ]);
    }

    public function test_guest_cannot_lookup_order(): void
    {
        $this->getJson(route('admin.ref-exchange.coinex-spot-orders.lookup', [
            'currency_id' => $this->currency->id,
            'order_id' => 13400,
        ]))->assertRedirect();
    }

    public function test_admin_without_permission_cannot_lookup_order(): void
    {
        $other = Admin::factory()->create();

        $this->actingAs($other, 'admin')
            ->getJson(route('admin.ref-exchange.coinex-spot-orders.lookup', [
                'currency_id' => $this->currency->id,
                'order_id' => 13400,
            ]))
            ->assertForbidden();
    }

    public function test_index_shows_order_id_search_box(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.ref-exchange.coinex-spot-orders.index'));

        dump($response->status(), $response->headers->get('Location'), app()->environment(), config('app.env'));

        $response
            ->assertOk()
            ->assertSee('جستجو بر اساس شناسه سفارش CoinEx')
            ->assertSee('lookup_order_id', false);
    }

    public function test_lookup_requires_currency_and_order_id(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->getJson(route('admin.ref-exchange.coinex-spot-orders.lookup'))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['currency_id', 'order_id']);
    }

    public function test_lookup_rejects_invalid_currency(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->getJson(route('admin.ref-exchange.coinex-spot-orders.lookup', [
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
            ->getJson(route('admin.ref-exchange.coinex-spot-orders.lookup', [
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
            ->getJson(route('admin.ref-exchange.coinex-spot-orders.lookup', [
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
            ->getJson(route('admin.ref-exchange.coinex-spot-orders.lookup', [
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

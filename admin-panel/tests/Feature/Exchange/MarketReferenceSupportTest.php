<?php

namespace Tests\Feature\Exchange;

use App\Models\Admin;
use App\Models\Currency;
use App\Models\Exchange;
use App\Models\ExchangePrice;
use App\Models\Market;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class MarketReferenceSupportTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    private Exchange $binance;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(
            ['name' => 'market', 'guard_name' => 'admin'],
            ['persian_name' => 'مدیریت بازارها']
        );

        $this->admin = Admin::factory()->create();
        $this->admin->refresh();
        $this->admin->givePermissionTo('market');

        $this->binance = Exchange::query()->where('slug', 'binance')->firstOrFail();
        $this->binance->forceFill([
            'name' => 'Binance',
            'is_active' => true,
        ])->save();

        Cache::flush();
    }

    public function test_index_flags_markets_missing_from_the_reference_exchange(): void
    {
        $this->fakeExchangeInfo(['BTCUSDT']);
        $utk = $this->makeMarket('UTK');
        $btc = $this->makeMarket('BTC');

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.market.index'));

        $response->assertOk();
        $html = $response->getContent();

        $response->assertSee('1 بازار روی صرافی مرجع پشتیبانی نمی‌شود');
        $response->assertSee('UTK/USDT — Binance');
        $response->assertSee('پشتیبانی نمی‌شود');
        $this->assertMatchesRegularExpression(
            '/data-market-id="' . $utk->id . '"[^>]*market-unsupported/',
            $html
        );
        $this->assertDoesNotMatchRegularExpression(
            '/data-market-id="' . $btc->id . '"[^>]*market-unsupported/',
            $html
        );
    }

    public function test_index_stays_up_when_the_reference_exchange_cannot_be_read(): void
    {
        Http::fake([
            '*/api/v3/exchangeInfo' => Http::response(['msg' => 'down'], 500),
        ]);
        $this->makeMarket('UTK');

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.market.index'))
            ->assertOk()
            ->assertSee('بررسی پشتیبانی بازارها روی صرافی مرجع انجام نشد')
            ->assertDontSee('class="market-unsupported"', false);
    }

    public function test_min_otc_reports_an_unsupported_market(): void
    {
        $this->fakeExchangeInfo(['BTCUSDT']);
        $utk = $this->makeMarket('UTK');

        $this->actingAs($this->admin, 'admin')
            ->getJson(route('admin.market.min-otc', ['market' => $utk]))
            ->assertOk()
            ->assertJsonPath('supported', false)
            ->assertJsonPath('exchange', 'Binance')
            ->assertJsonPath('symbol', 'UTK/USDT');
    }

    public function test_min_otc_returns_the_exchange_minimum_when_the_market_is_listed(): void
    {
        $this->fakeExchangeInfo(['BTCUSDT']);
        $btc = $this->makeMarket('BTC');

        $this->actingAs($this->admin, 'admin')
            ->getJson(route('admin.market.min-otc', ['market' => $btc, 'exchange_id' => $this->binance->id]))
            ->assertOk()
            ->assertJsonPath('supported', true)
            ->assertJsonPath('min_amount', '0.00001000')
            ->assertJsonPath('exchange', 'Binance');
    }

    public function test_edit_page_reserves_a_support_notice(): void
    {
        $market = $this->makeMarket('UTK');

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.market.edit', ['market' => $market]))
            ->assertOk()
            ->assertSee('در حال بررسی پشتیبانی این بازار روی صرافی مرجع', false);
    }

    private function fakeExchangeInfo(array $symbols): void
    {
        Http::fake([
            '*/api/v3/exchangeInfo' => Http::response([
                'symbols' => array_map(fn (string $symbol) => [
                    'symbol' => $symbol,
                    'status' => 'TRADING',
                    'baseAsset' => str_replace('USDT', '', $symbol),
                    'quoteAsset' => 'USDT',
                    'filters' => [
                        ['filterType' => 'LOT_SIZE', 'minQty' => '0.00001000'],
                    ],
                ], $symbols),
            ]),
        ]);
    }

    private function makeMarket(string $base): Market
    {
        $quote = Currency::query()->firstOrCreate(
            ['symbol' => 'USDT'],
            ['name' => 'Tether', 'persian_name' => 'تتر']
        );
        $currency = Currency::query()->firstOrCreate(
            ['symbol' => $base],
            ['name' => $base, 'persian_name' => $base]
        );

        $market = Market::query()->create([
            'base_currency' => $currency->symbol,
            'quote_currency' => $quote->symbol,
            'min_otc_amount' => 1,
            'max_otc_amount' => 10,
            'min_trade_amount' => 1,
            'max_trade_amount' => 10,
            'is_active' => true,
        ]);

        ExchangePrice::query()->create([
            'market_id' => $market->id,
            'exchange_id' => $this->binance->id,
            'price' => '1.50000000',
            'open_price' => '1.20000000',
            'exchange_profit_sell' => 1,
            'exchange_profit_buy' => 1,
        ]);

        return $market;
    }
}

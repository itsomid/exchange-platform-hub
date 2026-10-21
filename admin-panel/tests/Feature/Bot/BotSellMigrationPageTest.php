<?php

namespace Tests\Feature\Bot;

use App\Http\Middleware\AdminTwoFactorAuth;
use App\Http\Middleware\CheckUserActivation;
use App\Models\Admin;
use App\Models\Bot\BotBuyExecution;
use App\Models\Bot\BotOrder;
use App\Models\Bot\BotSellMigration;
use App\Models\Bot\BotSellOrder;
use App\Models\Currency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class BotSellMigrationPageTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;
    private Currency $currency;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([AdminTwoFactorAuth::class, CheckUserActivation::class]);

        Permission::firstOrCreate(
            ['name' => 'bot-management', 'guard_name' => 'admin'],
            ['persian_name' => 'مدیریت ربات معاملاتی']
        );
        $this->admin = Admin::factory()->create();
        $this->admin->givePermissionTo('bot-management');
        $this->admin->setAttribute('avatar', null);

        $this->currency = Currency::factory()->create(['symbol' => 'ZZZ']);

        $order = BotOrder::forceCreate([
            'user_id'           => User::factory()->create()->id,
            'batch_uuid'        => (string) \Illuminate\Support\Str::uuid(),
            'total_amount_usdt' => '100',
            'alpha_snapshot'    => 0.15,
            'status'            => 'PENDING',
            'triggered_by'      => 'MANUAL',
        ]);
        $execution = BotBuyExecution::forceCreate([
            'bot_order_id'    => $order->id,
            'currency_id'     => $this->currency->id,
            'signal_snapshot' => [],
            'allocated_usdt'  => '20',
            'filled_amount'   => '10',
            'avg_buy_price'   => '2',
            'status'          => 'BOUGHT',
        ]);
        foreach ([['OPEN', '6'], ['OPEN', '4'], ['FILLED', '5']] as [$status, $amount]) {
            BotSellOrder::forceCreate([
                'bot_buy_execution_id' => $execution->id,
                'exchange_order_id'    => 17914920817113505,
                'exchange'             => 'coinex',
                'target_type'          => 'percent',
                'target_value'         => 20,
                'share_percent'        => 50,
                'amount_to_sell'       => $amount,
                'status'               => $status,
            ]);
        }
    }

    public function test_guest_without_permission_cannot_open_the_page(): void
    {
        $other = Admin::factory()->create();
        $other->setAttribute('avatar', null);

        $this->actingAs($other, 'admin')
            ->get(route('admin.bot.sell-migration.index'))
            ->assertForbidden();
    }

    public function test_index_shows_required_amount_against_binance_balance(): void
    {
        Http::fake([
            '*/api/v3/account*' => Http::response(['balances' => [['asset' => 'ZZZ', 'free' => '7', 'locked' => '1']]]),
            '*'                 => Http::response(['serverTime' => 1_700_000_000_000]),
        ]);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.bot.sell-migration.index'))
            ->assertOk()
            ->assertSee('ZZZ')
            ->assertSee('کمبود')
            ->assertSee(route('admin.bot.sell-migration.start', $this->currency->id), false);
    }

    public function test_start_is_forwarded_to_api_service(): void
    {
        Http::fake(['*' => Http::response(['ok' => true, 'migration_id' => 4, 'status' => 'PENDING'])]);

        $this->actingAs($this->admin, 'admin')
            ->postJson(route('admin.bot.sell-migration.start', $this->currency->id))
            ->assertOk()
            ->assertJson(['ok' => true, 'migration_id' => 4]);

        Http::assertSent(fn (HttpRequest $r) => str_ends_with($r->url(), "/currencies/{$this->currency->id}/sell-migration"));
    }

    public function test_status_endpoint_returns_the_run(): void
    {
        $run = BotSellMigration::create([
            'currency_id' => $this->currency->id,
            'status'      => 'DONE',
            'tiers_count' => 2,
        ]);

        $this->actingAs($this->admin, 'admin')
            ->getJson(route('admin.bot.sell-migration.status', $run->id))
            ->assertOk()
            ->assertJsonPath('migration.finished', true)
            ->assertJsonPath('migration.currency_symbol', 'ZZZ');
    }

    public function test_report_lists_each_tier_with_its_outcome_and_failure_hint(): void
    {
        [$placed, $failed] = BotSellOrder::where('status', 'OPEN')->orderBy('id')->get()->all();
        $placed->forceFill(['exchange' => 'binance', 'exchange_order_id' => 555])->save();

        $run = BotSellMigration::create([
            'currency_id'  => $this->currency->id,
            'status'       => 'DONE',
            'tiers_count'  => 2,
            'placed_count' => 1,
            'failed_count' => 1,
            'details'      => ['live_price' => '2.1', 'close' => null, 'tiers' => [
                ['sell_order_id' => $placed->id, 'action' => 'limit', 'outcome' => 'placed', 'target_price' => '2.4', 'amount' => '6', 'new_exchange_order_id' => '555'],
                ['sell_order_id' => $failed->id, 'action' => 'limit', 'outcome' => 'failed', 'target_price' => '2.4', 'amount' => '4', 'error' => '-1013 Filter failure: PERCENT_PRICE_BY_SIDE'],
            ]],
        ]);

        Http::fake([
            '*/api/v3/exchangeInfo*' => Http::response(['symbols' => [['filters' => [
                ['filterType' => 'PERCENT_PRICE_BY_SIDE', 'askMultiplierUp' => '5', 'askMultiplierDown' => '0.2', 'avgPriceMins' => 5],
            ]]]]),
            '*/api/v3/avgPrice*' => Http::response(['mins' => 5, 'price' => '2']),
        ]);

        $this->actingAs($this->admin, 'admin')
            ->getJson(route('admin.bot.sell-migration.report', $run->id))
            ->assertOk()
            ->assertJsonPath('migration.live_price', '2.1')
            ->assertJsonMissingPath('migration.details')
            ->assertJsonPath('price_limit.max_percent_above', '400.00')
            ->assertJsonPath('price_limit.max_price', '10.00000000')
            ->assertJsonPath('rows.0.outcome', 'placed')
            ->assertJsonPath('rows.0.current.exchange', 'binance')
            ->assertJsonPath('rows.0.current.exchange_order_id', '555')
            ->assertJsonPath('rows.1.outcome', 'failed')
            ->assertJsonPath('rows.1.current.status', 'OPEN')
            ->assertJsonPath('rows.1.above_live_percent', '14.28')
            ->assertJsonPath('rows.1.error_hint', fn ($hint) => str_contains($hint, 'Binance'));
    }
}

<?php

namespace Tests\Feature\Bot;

use App\Http\Middleware\AdminTwoFactorAuth;
use App\Http\Middleware\CheckUserActivation;
use App\Models\Admin;
use App\Models\Bot\BotBuyExecution;
use App\Models\Bot\BotGlobalSettings;
use App\Models\Bot\BotOrder;
use App\Models\Bot\BotSellOrder;
use App\Models\Currency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class BotWaitingSellsPageTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;
    private Currency $currency;
    private BotSellOrder $waiting;

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

        $this->currency = Currency::factory()->create(['symbol' => 'WWW']);

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
        BotSellOrder::forceCreate([
            'bot_buy_execution_id' => $execution->id,
            'exchange_order_id'    => 555,
            'exchange'             => 'binance',
            'target_type'          => 'percent',
            'target_value'         => 20,
            'share_percent'        => 50,
            'amount_to_sell'       => '5',
            'status'               => 'OPEN',
        ]);
        $this->waiting = BotSellOrder::forceCreate([
            'bot_buy_execution_id' => $execution->id,
            'exchange'             => 'binance',
            'target_type'          => 'percent',
            'target_value'         => 400,
            'share_percent'        => 50,
            'amount_to_sell'       => '5',
            'status'               => 'OPEN',
            'placement_status'     => BotSellOrder::PLACEMENT_WAITING,
            'waiting_since'        => now(),
            'last_placement_error' => '-1013 Filter failure: PERCENT_PRICE_BY_SIDE',
        ]);
    }

    public function test_admin_without_permission_cannot_open_the_page(): void
    {
        $other = Admin::factory()->create();
        $other->setAttribute('avatar', null);

        $this->actingAs($other, 'admin')
            ->get(route('admin.bot.waiting-sells.index'))
            ->assertForbidden();
    }

    public function test_index_lists_only_waiting_tiers_per_coin_with_the_last_run(): void
    {
        Http::fake(['*/waiting-sells/last-run' => Http::response(['ok' => true, 'last_run' => [
            'at'       => now()->toIso8601String(),
            'disabled' => false,
            'totals'   => ['placed' => 3, 'completed' => 1, 'rejected' => 0, 'rotated' => 2],
        ]])]);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.bot.waiting-sells.index'))
            ->assertOk()
            ->assertSee('WWW')
            ->assertSee('1 پله')
            ->assertSee('ثبت 3 · Market 1 · جابه‌جایی 2 · رد 0')
            ->assertSee(route('admin.bot.waiting-sells.place-currency', $this->currency->id), false);
    }

    public function test_index_splits_waiting_tiers_by_reason_and_shows_order_slots(): void
    {
        BotSellOrder::forceCreate([
            'bot_buy_execution_id' => $this->waiting->bot_buy_execution_id,
            'exchange'             => 'binance',
            'target_type'          => 'percent',
            'target_value'         => 60,
            'share_percent'        => 10,
            'amount_to_sell'       => '1.5',
            'status'               => 'OPEN',
            'placement_status'     => BotSellOrder::PLACEMENT_WAITING,
            'waiting_reason'       => BotSellOrder::WAITING_ORDER_LIMIT,
            'waiting_since'        => now(),
        ]);
        Http::fake(['*/waiting-sells/last-run' => Http::response(['ok' => true, 'last_run' => [
            'at'         => now()->toIso8601String(),
            'disabled'   => false,
            'totals'     => ['placed' => 0, 'completed' => 0, 'rejected' => 0, 'rotated' => 0],
            'currencies' => [[
                'currency_id' => $this->currency->id,
                'slots'       => ['max' => 200, 'reserve' => 10, 'open' => 190, 'free' => 0],
            ]],
        ]])]);

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.bot.waiting-sells.index'))
            ->assertOk();

        $rows = $response->viewData('rows');
        $this->assertSame(1, $rows[0]->price_tiers);
        $this->assertSame(1, $rows[0]->slot_tiers);
        $this->assertSame(1.5, $rows[0]->slot_amount);
        $this->assertSame(1, $rows[0]->placed_tiers);
        $response->assertSee('data-reasons="price slot"', false)
            ->assertSee('0 جای خالی');
    }

    public function test_index_still_renders_when_api_service_is_down(): void
    {
        Http::fake(['*' => Http::response('down', 500)]);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.bot.waiting-sells.index'))
            ->assertOk()
            ->assertSee('اطلاعاتی از api-service دریافت نشد');
    }

    public function test_order_page_marks_the_waiting_tier(): void
    {
        $orderId = BotBuyExecution::find($this->waiting->bot_buy_execution_id)->bot_order_id;

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.bot.order.show', $orderId))
            ->assertOk()
            ->assertSee('هنوز سفارشی در صرافی ندارد')
            ->assertSee('(سقف قیمت)')
            ->assertSee('PERCENT_PRICE_BY_SIDE');

        $this->waiting->forceFill(['waiting_reason' => BotSellOrder::WAITING_ORDER_LIMIT])->save();

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.bot.order.show', $orderId))
            ->assertOk()
            ->assertSee('(سقف تعداد سفارش)')
            ->assertSee('سقف تعداد سفارش باز این بازار در Binance پر است');
    }

    public function test_auto_place_toggle_is_stored_in_global_settings(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->postJson(route('admin.bot.waiting-sells.auto-place'), ['enabled' => false])
            ->assertOk()
            ->assertJson(['ok' => true, 'enabled' => false]);

        $this->assertFalse((bool) BotGlobalSettings::current()->waiting_sells_auto_place);
    }

    public function test_settings_page_shows_waiting_tier_defaults(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.bot.settings.index'))
            ->assertOk()
            ->assertSeeInOrder(['name="waiting_max_per_run"', 'value="200"'], false)
            ->assertSeeInOrder(['name="waiting_order_slot_reserve"', 'value="10"'], false)
            ->assertSeeInOrder(['name="waiting_rotation_max_per_run"', 'value="30"'], false)
            ->assertSee('id="waiting_rotation_enabled"', false);
    }

    public function test_settings_page_saves_waiting_tier_settings(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->patch(route('admin.bot.settings.update'), [
                'min_deposit_usdt'                 => 20,
                'alpha_weight'                     => 0.15,
                'default_sell_orders_count'        => 3,
                'performance_fee_percent'          => 22,
                'referral_fee_percent'             => 4,
                'p2p_min_order_value'              => 5,
                'precheck_floor_mode'              => 'multi',
                'transfer_fee_tiers'               => [['from' => 20, 'to' => null, 'fee_type' => 'flat', 'fee_value' => 1]],
                'waiting_safety_margin_percent'    => 3,
                'waiting_max_per_run'              => 150,
                'waiting_order_slot_reserve'       => 15,
                'waiting_rotation_max_per_run'     => 20,
                'waiting_rotation_min_gap_percent' => 12.5,
            ])
            ->assertRedirect(route('admin.bot.settings.index'))
            ->assertSessionHasNoErrors();

        $settings = BotGlobalSettings::current();
        $this->assertSame('3.00', $settings->waiting_safety_margin_percent);
        $this->assertSame(150, $settings->waiting_max_per_run);
        $this->assertSame(15, $settings->waiting_order_slot_reserve);
        $this->assertFalse($settings->waiting_rotation_enabled);
        $this->assertSame(20, $settings->waiting_rotation_max_per_run);
        $this->assertSame('12.50', $settings->waiting_rotation_min_gap_percent);
    }

    public function test_place_actions_are_forwarded_to_api_service(): void
    {
        Http::fake(['*' => Http::response(['ok' => true, 'message' => 'done'])]);

        $this->actingAs($this->admin, 'admin')
            ->postJson(route('admin.bot.waiting-sells.place-currency', $this->currency->id))
            ->assertOk();
        $this->actingAs($this->admin, 'admin')
            ->postJson(route('admin.bot.waiting-sells.place-tier', $this->waiting->id))
            ->assertOk();

        Http::assertSent(fn (HttpRequest $r) => str_ends_with($r->url(), "/waiting-sells/currencies/{$this->currency->id}/place"));
        Http::assertSent(fn (HttpRequest $r) => str_ends_with($r->url(), "/waiting-sells/{$this->waiting->id}/place"));
    }
}

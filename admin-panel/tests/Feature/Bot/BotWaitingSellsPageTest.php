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
            'totals'   => ['placed' => 3, 'completed' => 1, 'rejected' => 0],
        ]])]);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.bot.waiting-sells.index'))
            ->assertOk()
            ->assertSee('WWW')
            ->assertSee('1 پله')
            ->assertSee('ثبت 3 · Market 1 · رد 0')
            ->assertSee(route('admin.bot.waiting-sells.place-currency', $this->currency->id), false);
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
            ->assertSee('PERCENT_PRICE_BY_SIDE');
    }

    public function test_auto_place_toggle_is_stored_in_global_settings(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->postJson(route('admin.bot.waiting-sells.auto-place'), ['enabled' => false])
            ->assertOk()
            ->assertJson(['ok' => true, 'enabled' => false]);

        $this->assertFalse((bool) BotGlobalSettings::current()->waiting_sells_auto_place);
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

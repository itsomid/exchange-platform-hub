<?php

namespace Tests\Feature\Bot;

use App\Models\Admin;
use App\Models\Bot\BotAutoTradeEvent;
use App\Models\Bot\BotOrder;
use App\Models\Bot\BotUserSettings;
use App\Models\Bot\BotWallet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class BotOrderControllerTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'bot-management', 'guard_name' => 'admin'],
            ['persian_name' => 'مدیریت ربات معاملاتی']);

        $this->admin = Admin::factory()->create();
        $this->admin->givePermissionTo('bot-management');
    }

    // ── Authorization ──────────────────────────────────────────────────────

    public function test_guest_cannot_view_orders(): void
    {
        $this->get(route('admin.bot.order.index'))->assertRedirect();
    }

    public function test_admin_without_permission_cannot_view_orders(): void
    {
        $other = Admin::factory()->create();
        $this->actingAs($other, 'admin')
            ->get(route('admin.bot.order.index'))
            ->assertForbidden();
    }

    // ── Index ──────────────────────────────────────────────────────────────

    public function test_authorized_admin_can_view_orders_index(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.bot.order.index'))
            ->assertOk()
            ->assertViewIs('dashboard.bot.orders.index');
    }

    public function test_index_shows_orders_with_user(): void
    {
        $user = User::factory()->create();
        BotOrder::create([
            'user_id'           => $user->id,
            'batch_uuid'        => Str::uuid(),
            'total_amount_usdt' => '100.00000000',
            'alpha_snapshot'    => '0.15',
            'status'            => 'PENDING',
        ]);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.bot.order.index'))
            ->assertOk()
            ->assertSee($user->email ?? $user->mobile);
    }

    public function test_index_sorts_by_free_balance_and_preserves_query_string(): void
    {
        $low = User::factory()->create(['email' => 'low@example.com']);
        $high = User::factory()->create(['email' => 'high@example.com']);

        foreach ([$low, $high] as $user) {
            BotOrder::create([
                'user_id'           => $user->id,
                'batch_uuid'        => Str::uuid(),
                'total_amount_usdt' => '50.00000000',
                'alpha_snapshot'    => '0.15',
                'status'            => 'PENDING',
            ]);
        }

        BotWallet::create([
            'user_id'           => $low->id,
            'balance'           => '100.00000000',
            'principal_balance' => '100.00000000',
            'profit_balance'    => '0.00000000',
            'locked_balance'    => '90.00000000',
        ]);
        BotWallet::create([
            'user_id'           => $high->id,
            'balance'           => '200.00000000',
            'principal_balance' => '200.00000000',
            'profit_balance'    => '25.00000000',
            'locked_balance'    => '20.00000000',
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.bot.order.index', [
                'sort' => 'free_balance',
                'dir'  => 'desc',
            ]))
            ->assertOk();

        $rows = $response->viewData('rows');
        $this->assertSame($high->id, $rows->items()[0]->user_id);
        $this->assertEquals(180.0, (float) $rows->items()[0]->free_balance);
        $this->assertSame($low->id, $rows->items()[1]->user_id);
        $this->assertEquals(10.0, (float) $rows->items()[1]->free_balance);
        $this->assertSame('free_balance', $response->viewData('sort'));
        $this->assertSame('desc', $response->viewData('dir'));
    }

    public function test_index_ignores_invalid_sort_and_defaults_to_last_order(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.bot.order.index', ['sort' => 'hack;drop', 'dir' => 'nope']))
            ->assertOk()
            ->assertViewHas('sort', 'last_order_at')
            ->assertViewHas('dir', 'desc');
    }

    // ── Show ───────────────────────────────────────────────────────────────

    public function test_show_returns_200_for_existing_order(): void
    {
        $user = User::factory()->create();
        $order = BotOrder::create([
            'user_id'           => $user->id,
            'batch_uuid'        => Str::uuid(),
            'total_amount_usdt' => '100.00000000',
            'alpha_snapshot'    => '0.15',
            'status'            => 'PENDING',
        ]);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.bot.order.show', $order))
            ->assertOk()
            ->assertViewIs('dashboard.bot.orders.show');
    }

    public function test_admin_can_update_order_description(): void
    {
        $user = User::factory()->create();
        $order = BotOrder::create([
            'user_id'           => $user->id,
            'batch_uuid'        => Str::uuid(),
            'total_amount_usdt' => '100.00000000',
            'alpha_snapshot'    => '0.15',
            'status'            => 'PENDING',
        ]);

        $this->actingAs($this->admin, 'admin')
            ->patchJson(route('admin.bot.order.update-description', $order), [
                'admin_description' => 'تماس با کاربر برای پیگیری',
            ])
            ->assertOk()
            ->assertJsonPath('ok', true);

        $this->assertSame(
            'تماس با کاربر برای پیگیری',
            $order->fresh()->admin_description,
        );
    }

    // ── No write routes ────────────────────────────────────────────────────

    public function test_post_to_orders_returns_404(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->post('/admin/auto-trade/orders')
            ->assertNotFound();
    }

    public function test_delete_order_returns_404(): void
    {
        $user = User::factory()->create();
        $order = BotOrder::create([
            'user_id'           => $user->id,
            'batch_uuid'        => Str::uuid(),
            'total_amount_usdt' => '100.00000000',
            'alpha_snapshot'    => '0.15',
            'status'            => 'PENDING',
        ]);

        $this->actingAs($this->admin, 'admin')
            ->delete('/admin/auto-trade/orders/'.$order->id)
            ->assertNotFound();
    }

    public function test_admin_toggle_records_reason_and_user_page_shows_last_change(): void
    {
        $user = User::factory()->create([
            'email'  => 'audit-user@example.com',
            'mobile' => '09121114113',
        ]);

        $this->actingAs($this->admin, 'admin');
        $controller = $this->app->make(\App\Http\Controllers\Admin\Bot\BotOrderController::class);

        $on = $controller->toggleAutoTrade($user)->getData(true);
        $this->assertTrue($on['enabled']);

        $off = $controller->toggleAutoTrade($user)->getData(true);
        $this->assertFalse($off['enabled']);
        $this->assertSame(BotAutoTradeEvent::REASON_ADMIN_TOGGLE, $off['last_disable']['reason_code']);
        $this->assertStringContainsString('ادمین سوئیچ خرید و فروش خودکار', $off['last_disable']['reason']);

        $this->assertDatabaseHas('bot_auto_trade_events', [
            'user_id'     => $user->id,
            'enabled'     => 0,
            'source'      => BotAutoTradeEvent::SOURCE_ADMIN,
            'reason_code' => BotAutoTradeEvent::REASON_ADMIN_TOGGLE,
            'actor_id'    => $this->admin->id,
        ]);
        $this->assertFalse((bool) BotUserSettings::where('user_id', $user->id)->value('auto_trade_enabled'));

        $view = $controller->userShow($user);
        $this->assertSame('dashboard.bot.orders.user', $view->name());
        $this->assertFalse((bool) $view->getData()['settings']->auto_trade_enabled);
        $this->assertNotNull($view->getData()['lastChange']);
        $this->assertNotNull($view->getData()['lastDisable']);
        $this->assertSame(
            BotAutoTradeEvent::REASON_ADMIN_TOGGLE,
            $view->getData()['lastDisable']->reason_code,
        );
        $this->assertStringContainsString(
            'ادمین سوئیچ خرید و فروش خودکار را از صفحه کاربر خاموش کرد.',
            $view->getData()['lastDisable']->reason,
        );
    }
}

<?php

namespace Tests\Feature\Bot;

use App\Models\Admin;
use App\Models\Bot\BotOrder;
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
}

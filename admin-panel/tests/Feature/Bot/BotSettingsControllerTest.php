<?php

namespace Tests\Feature\Bot;

use App\Models\Admin;
use App\Models\Bot\BotGlobalSettings;
use App\Models\Bot\BotSignal;
use App\Models\Currency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class BotSettingsControllerTest extends TestCase
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

    public function test_guest_cannot_view_settings(): void
    {
        $this->get(route('admin.bot.settings.index'))
            ->assertRedirect();
    }

    public function test_admin_without_permission_cannot_view_settings(): void
    {
        $other = Admin::factory()->create();

        $this->actingAs($other, 'admin')
            ->get(route('admin.bot.settings.index'))
            ->assertForbidden();
    }

    // ── GET /settings ──────────────────────────────────────────────────────

    public function test_authorized_admin_can_view_settings_page(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.bot.settings.index'))
            ->assertOk()
            ->assertViewIs('dashboard.bot.settings.index');
    }

    public function test_settings_page_shows_existing_settings(): void
    {
        BotGlobalSettings::create([
            'min_deposit_usdt'          => '50.00000000',
            'alpha_weight'              => '0.20',
            'default_sell_orders_count' => 5,
            'performance_fee_percent'   => '15.00',
            'transfer_fee_tiers'        => [],
            'is_enabled'                => true,
        ]);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.bot.settings.index'))
            ->assertOk()
            ->assertSee('50');
    }

    // ── PATCH /settings ────────────────────────────────────────────────────

    public function test_authorized_admin_can_update_settings(): void
    {
        BotGlobalSettings::create([
            'min_deposit_usdt'          => '20.00000000',
            'alpha_weight'              => '0.15',
            'default_sell_orders_count' => 3,
            'performance_fee_percent'   => '22.00',
            'transfer_fee_tiers'        => [],
            'is_enabled'                => true,
        ]);

        $payload = [
            'min_deposit_usdt'          => 30,
            'alpha_weight'              => 0.20,
            'default_sell_orders_count' => 4,
            'performance_fee_percent'   => 18,
            'is_enabled'                => 1,
            'transfer_fee_tiers'        => [
                ['from' => 20, 'to' => 100,  'fee_type' => 'flat',    'fee_value' => 1],
                ['from' => 100, 'to' => 1000, 'fee_type' => 'percent', 'fee_value' => 1],
                ['from' => 1000, 'to' => null, 'fee_type' => 'flat',   'fee_value' => 12],
            ],
        ];

        $this->actingAs($this->admin, 'admin')
            ->patch(route('admin.bot.settings.update'), $payload)
            ->assertRedirect(route('admin.bot.settings.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('bot_global_settings', [
            'default_sell_orders_count' => 4,
            'performance_fee_percent'   => '18.00',
        ]);
    }

    private function signal(bool $active, bool $pending = false): BotSignal
    {
        return BotSignal::create([
            'currency_id' => Currency::factory()->create()->id,
            'priority' => 1,
            'floor_price' => 1,
            'ceiling_price' => 2,
            'min_buy_amount_usdt' => 5,
            'max_allocation_percent' => 100,
            'sell_orders_count' => 3,
            'sell_mode' => 'percent',
            'sell_targets' => [['share' => 100, 'target' => 10]],
            'is_active' => $active,
            'activation_pending_at' => $pending ? now()->subHour() : null,
        ]);
    }

    private function saveGlobalEnabled(bool $enabled): void
    {
        // Refresh to hydrate database defaults (notably is_active) so the real
        // admin authentication/activation middleware can run normally.
        $this->actingAs($this->admin->refresh(), 'admin')
            ->patch(route('admin.bot.settings.update'), array_merge(
                BotGlobalSettings::current()->toArray(),
                [
                    'is_enabled' => $enabled,
                    'transfer_fee_tiers' => [['from' => 20, 'to' => null, 'fee_type' => 'flat', 'fee_value' => 1]],
                ],
            ))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.bot.settings.index'))
            ->assertSessionHas('success');
    }

    public function test_reenabling_marks_all_active_signals_for_the_next_buy_scan(): void
    {
        BotGlobalSettings::current()->fill(['is_enabled' => false])->save();
        $active = $this->signal(active: true);
        $pending = $this->signal(active: true, pending: true);
        $inactive = $this->signal(active: false);

        $this->saveGlobalEnabled(true);

        $this->assertTrue(BotGlobalSettings::current()->is_enabled);
        $this->assertNotNull($active->fresh()->activation_pending_at);
        $this->assertNotNull($pending->fresh()->activation_pending_at);
        $this->assertNull($inactive->fresh()->activation_pending_at);
    }

    public function test_saving_enabled_settings_again_does_not_trigger_another_buy_scan(): void
    {
        BotGlobalSettings::current()->save();
        $signal = $this->signal(active: true);

        $this->saveGlobalEnabled(true);

        $this->assertTrue(BotGlobalSettings::current()->is_enabled);
        $this->assertNull($signal->fresh()->activation_pending_at);
    }

    public function test_disabling_and_saving_disabled_settings_preserves_pending_activations(): void
    {
        BotGlobalSettings::current()->save();
        $signal = $this->signal(active: true, pending: true);
        $pendingAt = $signal->fresh()->activation_pending_at;
        $unmarked = $this->signal(active: true);

        $this->saveGlobalEnabled(false);
        $this->saveGlobalEnabled(false);

        $this->assertFalse(BotGlobalSettings::current()->is_enabled);
        $this->assertTrue($signal->fresh()->activation_pending_at->equalTo($pendingAt));
        $this->assertNull($unmarked->fresh()->activation_pending_at);
    }

    public function test_update_settings_validates_required_fields(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->patch(route('admin.bot.settings.update'), [])
            ->assertSessionHasErrors(['min_deposit_usdt', 'alpha_weight', 'performance_fee_percent']);
    }

    public function test_update_settings_rejects_alpha_above_one(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->patch(route('admin.bot.settings.update'), [
                'min_deposit_usdt'          => 20,
                'alpha_weight'              => 1.5,
                'default_sell_orders_count' => 3,
                'performance_fee_percent'   => 22,
                'transfer_fee_tiers'        => [['from' => 20, 'to' => null, 'fee_type' => 'flat', 'fee_value' => 1]],
            ])
            ->assertSessionHasErrors('alpha_weight');
    }
}

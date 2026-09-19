<?php

namespace Tests\Feature\Bot;

use App\Models\Admin;
use App\Models\Bot\BotGlobalSettings;
use App\Models\Bot\BotSignal;
use App\Models\Currency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * P1.5 feature tests.
 *
 * Covers:
 * - p2p_min_order_value can be set and read back via the settings form
 * - Existing P2 settings form still passes WITHOUT sending the new field (backward compat)
 * - p2p_min_order_value_override on signal forms is optional (nullable)
 * - p2p_min_order_value_override rejects values out of range
 */
class BotP15FeatureTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;
    private Currency $currency;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(
            ['name' => 'bot-management', 'guard_name' => 'admin'],
            ['persian_name' => 'مدیریت ربات معاملاتی']
        );

        $this->admin = Admin::factory()->create();
        $this->admin->givePermissionTo('bot-management');

        $this->currency = Currency::factory()->create();
    }

    // ── Settings: p2p_min_order_value ──────────────────────────────────────

    public function test_p2p_min_order_value_can_be_saved_and_read_back(): void
    {
        BotGlobalSettings::create([
            'min_deposit_usdt'          => 20,
            'alpha_weight'              => 0.15,
            'default_sell_orders_count' => 3,
            'performance_fee_percent'   => 22,
            'p2p_min_order_value'       => 5,
            'transfer_fee_tiers'        => [],
            'is_enabled'                => true,
        ]);

        $payload = [
            'min_deposit_usdt'          => 25,
            'alpha_weight'              => 0.20,
            'default_sell_orders_count' => 3,
            'performance_fee_percent'   => 22,
            'p2p_min_order_value'       => 7.5,
            'is_enabled'                => 1,
            'transfer_fee_tiers'        => [
                ['from' => 20, 'to' => null, 'fee_type' => 'flat', 'fee_value' => 1],
            ],
        ];

        $this->actingAs($this->admin, 'admin')
            ->patch(route('admin.bot.settings.update'), $payload)
            ->assertRedirect(route('admin.bot.settings.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('bot_global_settings', ['p2p_min_order_value' => 7.5]);
    }

    public function test_settings_form_rejects_p2p_min_below_minimum(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->patch(route('admin.bot.settings.update'), [
                'min_deposit_usdt'          => 20,
                'alpha_weight'              => 0.15,
                'default_sell_orders_count' => 3,
                'performance_fee_percent'   => 22,
                'p2p_min_order_value'       => 0.05, // below 0.1
                'transfer_fee_tiers'        => [
                    ['from' => 20, 'to' => null, 'fee_type' => 'flat', 'fee_value' => 1],
                ],
            ])
            ->assertSessionHasErrors('p2p_min_order_value');
    }

    // ── Backward compatibility: existing P2 signal store without new field ─

    public function test_signal_store_passes_without_p2p_min_override(): void
    {
        // Sending a valid P2-era payload (no p2p_min_order_value_override) must still work.
        $payload = [
            'currency_id'            => $this->currency->id,
            'priority'               => 5,
            'floor_price'            => 0.01,
            'ceiling_price'          => 1.00,
            'min_buy_amount_usdt'    => 10,
            'max_allocation_percent' => 20,
            'sell_orders_count'      => 3,
            'sell_mode'              => 'percent',
            'sell_targets'           => [
                ['trigger' => 20, 'share' => 50],
                ['trigger' => 40, 'share' => 50],
            ],
            'is_active' => 1,
            // p2p_min_order_value_override intentionally omitted
        ];

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.bot.signal.store'), $payload)
            ->assertRedirect(route('admin.bot.signal.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('bot_signals', [
            'currency_id'                  => $this->currency->id,
            'p2p_min_order_value_override' => null,
        ]);
    }

    // ── Signal: p2p_min_order_value_override ──────────────────────────────

    public function test_signal_store_saves_p2p_min_override_when_provided(): void
    {
        $payload = [
            'currency_id'                  => $this->currency->id,
            'priority'                     => 5,
            'floor_price'                  => 0.01,
            'ceiling_price'                => 1.00,
            'min_buy_amount_usdt'          => 10,
            'max_allocation_percent'       => 20,
            'p2p_min_order_value_override' => 8.0,
            'sell_orders_count'            => 3,
            'sell_mode'                    => 'percent',
            'sell_targets'                 => [
                ['trigger' => 20, 'share' => 50],
                ['trigger' => 40, 'share' => 50],
            ],
            'is_active' => 1,
        ];

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.bot.signal.store'), $payload)
            ->assertRedirect(route('admin.bot.signal.index'));

        $this->assertDatabaseHas('bot_signals', [
            'currency_id'                  => $this->currency->id,
            'p2p_min_order_value_override' => 8.0,
        ]);
    }

    public function test_signal_store_rejects_p2p_min_override_below_minimum(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.bot.signal.store'), [
                'currency_id'                  => $this->currency->id,
                'priority'                     => 5,
                'floor_price'                  => 0.01,
                'ceiling_price'                => 1.00,
                'min_buy_amount_usdt'          => 10,
                'max_allocation_percent'       => 20,
                'p2p_min_order_value_override' => 0.05, // below 0.1
                'sell_orders_count'            => 3,
                'sell_mode'                    => 'percent',
                'sell_targets'                 => [
                    ['trigger' => 20, 'share' => 50],
                    ['trigger' => 40, 'share' => 50],
                ],
            ])
            ->assertSessionHasErrors('p2p_min_order_value_override');
    }

    public function test_signal_update_clears_override_when_empty(): void
    {
        $signal = BotSignal::create([
            'currency_id'                  => $this->currency->id,
            'priority'                     => 5,
            'floor_price'                  => '0.01',
            'ceiling_price'                => '1.00',
            'min_buy_amount_usdt'          => '10.00000000',
            'max_allocation_percent'       => '20.00',
            'p2p_min_order_value_override' => '8.00000000',
            'sell_orders_count'            => 3,
            'sell_mode'                    => 'percent',
            'sell_targets'                 => [['trigger' => 20, 'share' => 100]],
            'is_active'                    => true,
        ]);

        $this->actingAs($this->admin, 'admin')
            ->patch(route('admin.bot.signal.update', $signal), [
                'currency_id'                  => $this->currency->id,
                'priority'                     => 5,
                'floor_price'                  => 0.01,
                'ceiling_price'                => 1.00,
                'min_buy_amount_usdt'          => 10,
                'max_allocation_percent'       => 20,
                'p2p_min_order_value_override' => '',  // cleared
                'sell_orders_count'            => 3,
                'sell_mode'                    => 'percent',
                'sell_targets'                 => [['trigger' => 20, 'share' => 100]],
                'is_active'                    => 1,
            ])
            ->assertRedirect(route('admin.bot.signal.index'));

        $this->assertDatabaseHas('bot_signals', [
            'id'                           => $signal->id,
            'p2p_min_order_value_override' => null,
        ]);
    }
}

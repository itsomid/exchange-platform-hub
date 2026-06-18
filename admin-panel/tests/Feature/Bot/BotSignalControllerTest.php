<?php

namespace Tests\Feature\Bot;

use App\Models\Admin;
use App\Models\Bot\BotSignal;
use App\Models\Currency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class BotSignalControllerTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;
    private Currency $currency;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'bot-management', 'guard_name' => 'admin'],
            ['persian_name' => 'مدیریت ربات معاملاتی']);

        $this->admin = Admin::factory()->create();
        $this->admin->givePermissionTo('bot-management');

        $this->currency = Currency::factory()->create();
    }

    // ── Authorization ──────────────────────────────────────────────────────

    public function test_guest_cannot_access_signals(): void
    {
        $this->get(route('admin.bot.signal.index'))->assertRedirect();
    }

    public function test_admin_without_permission_cannot_access_signals(): void
    {
        $other = Admin::factory()->create();
        $this->actingAs($other, 'admin')
            ->get(route('admin.bot.signal.index'))
            ->assertForbidden();
    }

    // ── Index ──────────────────────────────────────────────────────────────

    public function test_authorized_admin_can_view_signals_index(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.bot.signal.index'))
            ->assertOk()
            ->assertViewIs('dashboard.bot.signals.index');
    }

    // ── Create ─────────────────────────────────────────────────────────────

    public function test_create_form_returns_200(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.bot.signal.create'))
            ->assertOk()
            ->assertViewIs('dashboard.bot.signals.create');
    }

    // ── Store ──────────────────────────────────────────────────────────────

    private function validSignalPayload(array $overrides = []): array
    {
        return array_merge([
            'currency_id'            => $this->currency->id,
            'priority'               => 5,
            'floor_price'            => 0.01,
            'ceiling_price'          => 1.00,
            'min_buy_amount_usdt'    => 10,
            'max_allocation_percent' => 20,
            'sell_orders_count'      => 3,
            'sell_mode'              => 'percent',
            'sell_targets'           => [
                ['share' => 50, 'target' => 10],
                ['share' => 50, 'target' => 20],
            ],
            'is_active' => 1,
        ], $overrides);
    }

    public function test_store_creates_signal_and_redirects(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.bot.signal.store'), $this->validSignalPayload())
            ->assertRedirect(route('admin.bot.signal.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('bot_signals', ['currency_id' => $this->currency->id]);
    }

    public function test_store_rejects_floor_price_above_ceiling(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.bot.signal.store'), $this->validSignalPayload([
                'floor_price'   => 2.00,
                'ceiling_price' => 1.00,
            ]))
            ->assertSessionHasErrors('floor_price');
    }

    public function test_store_rejects_sell_targets_sum_not_100(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.bot.signal.store'), $this->validSignalPayload([
                'sell_targets' => [
                    ['share' => 30, 'target' => 10],
                    ['share' => 30, 'target' => 20],
                ],
            ]))
            ->assertSessionHasErrors('sell_targets');
    }

    public function test_store_rejects_duplicate_currency(): void
    {
        BotSignal::create([
            'currency_id'            => $this->currency->id,
            'priority'               => 5,
            'floor_price'            => '0.01',
            'ceiling_price'          => '1.00',
            'min_buy_amount_usdt'    => '10.00000000',
            'max_allocation_percent' => '20.00',
            'sell_orders_count'      => 3,
            'sell_mode'              => 'percent',
            'sell_targets'           => [['share' => 100, 'target' => 10]],
            'is_active'              => true,
        ]);

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.bot.signal.store'), $this->validSignalPayload())
            ->assertSessionHasErrors('currency_id');
    }

    // ── Edit ───────────────────────────────────────────────────────────────

    public function test_edit_form_returns_200(): void
    {
        $signal = BotSignal::create([
            'currency_id'            => $this->currency->id,
            'priority'               => 5,
            'floor_price'            => '0.01',
            'ceiling_price'          => '1.00',
            'min_buy_amount_usdt'    => '10.00000000',
            'max_allocation_percent' => '20.00',
            'sell_orders_count'      => 3,
            'sell_mode'              => 'percent',
            'sell_targets'           => [['share' => 100, 'target' => 10]],
            'is_active'              => true,
        ]);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.bot.signal.edit', $signal))
            ->assertOk()
            ->assertViewIs('dashboard.bot.signals.edit');
    }

    // ── Update ─────────────────────────────────────────────────────────────

    public function test_update_saves_changes_and_redirects(): void
    {
        $signal = BotSignal::create([
            'currency_id'            => $this->currency->id,
            'priority'               => 5,
            'floor_price'            => '0.01',
            'ceiling_price'          => '1.00',
            'min_buy_amount_usdt'    => '10.00000000',
            'max_allocation_percent' => '20.00',
            'sell_orders_count'      => 3,
            'sell_mode'              => 'percent',
            'sell_targets'           => [['share' => 100, 'target' => 10]],
            'is_active'              => true,
        ]);

        $newCurrency = Currency::factory()->create();

        $this->actingAs($this->admin, 'admin')
            ->patch(route('admin.bot.signal.update', $signal), $this->validSignalPayload([
                'currency_id' => $newCurrency->id,
                'priority'    => 8,
            ]))
            ->assertRedirect(route('admin.bot.signal.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('bot_signals', ['id' => $signal->id, 'priority' => 8]);
    }

    // ── Destroy ────────────────────────────────────────────────────────────

    public function test_destroy_deletes_signal_and_redirects(): void
    {
        $signal = BotSignal::create([
            'currency_id'            => $this->currency->id,
            'priority'               => 5,
            'floor_price'            => '0.01',
            'ceiling_price'          => '1.00',
            'min_buy_amount_usdt'    => '10.00000000',
            'max_allocation_percent' => '20.00',
            'sell_orders_count'      => 3,
            'sell_mode'              => 'percent',
            'sell_targets'           => [['share' => 100, 'target' => 10]],
            'is_active'              => true,
        ]);

        $this->actingAs($this->admin, 'admin')
            ->delete(route('admin.bot.signal.destroy', $signal))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('bot_signals', ['id' => $signal->id]);
    }

    // ── Toggle status ──────────────────────────────────────────────────────

    public function test_toggle_status_flips_is_active(): void
    {
        $signal = BotSignal::create([
            'currency_id'            => $this->currency->id,
            'priority'               => 5,
            'floor_price'            => '0.01',
            'ceiling_price'          => '1.00',
            'min_buy_amount_usdt'    => '10.00000000',
            'max_allocation_percent' => '20.00',
            'sell_orders_count'      => 3,
            'sell_mode'              => 'percent',
            'sell_targets'           => [['share' => 100, 'target' => 10]],
            'is_active'              => true,
        ]);

        $this->actingAs($this->admin, 'admin')
            ->patch(route('admin.bot.signal.toggle-status', $signal))
            ->assertRedirect();

        $this->assertDatabaseHas('bot_signals', ['id' => $signal->id, 'is_active' => false]);
    }
}

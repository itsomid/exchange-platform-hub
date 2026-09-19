<?php

use App\Actions\Bot\BotBuyOrchestrator;
use App\Events\Bot\BotAutoTradeToggled;
use App\Models\Bot\BotAutoTradeEvent;
use App\Models\Bot\BotBuyExecution;
use App\Models\Bot\BotOrder;
use App\Models\Bot\BotUserSettings;
use App\Models\User;
use App\Services\Bot\BotOrderStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function makeToggleUser(array $overrides = []): User
{
    return User::factory()->create(array_merge([
        'email'             => 'toggle-'.uniqid().'@example.test',
        'email_verified_at' => now(),
        'status'            => 'active',
        'mobile'            => '0912'.rand(1000000, 9999999),
    ], $overrides));
}

function enableAutoTrade(User $user): BotUserSettings
{
    return BotUserSettings::create([
        'user_id'            => $user->id,
        'auto_trade_enabled' => true,
        'reinvest_enabled'   => false,
    ]);
}

function makeAllFailedPendingOrder(User $user, string $triggeredBy): BotOrder
{
    $currencyId = DB::table('currencies')->insertGetId([
        'name'             => 'Bitcoin',
        'persian_name'     => 'بیتکوین',
        'symbol'           => 'BTC'.uniqid(),
        'price_precision'  => 2,
        'amount_precision' => 8,
        'created_at'       => now(),
        'updated_at'       => now(),
    ]);

    $order = BotOrder::create([
        'user_id'           => $user->id,
        'batch_uuid'        => (string) \Illuminate\Support\Str::uuid(),
        'total_amount_usdt' => '100',
        'alpha_snapshot'    => 0.15,
        'status'            => 'PENDING',
        'triggered_by'      => $triggeredBy,
    ]);

    BotBuyExecution::create([
        'bot_order_id'    => $order->id,
        'currency_id'     => $currencyId,
        'signal_snapshot' => [],
        'allocated_usdt'  => '100',
        'filled_amount'   => 0,
        'status'          => BotBuyExecution::STATUS_FAILED,
        'failure_reason'  => 'market buy failed',
    ]);

    return $order;
}

it('logs and records when the user turns auto-trade off from settings', function () {
    Event::fake([BotAutoTradeToggled::class]);

    $user = makeToggleUser(['email' => 'user-off@example.test']);
    enableAutoTrade($user);

    Sanctum::actingAs($user);

    $this->patchJson('/api/v1/bot/settings', ['auto_trade_enabled' => false])
        ->assertOk()
        ->assertJsonPath('data.auto_trade_enabled', false);

    $this->assertDatabaseHas('bot_user_settings', [
        'user_id'            => $user->id,
        'auto_trade_enabled' => 0,
    ]);
    $this->assertDatabaseHas('bot_auto_trade_events', [
        'user_id'     => $user->id,
        'enabled'     => 0,
        'source'      => BotAutoTradeEvent::SOURCE_USER,
        'reason_code' => BotAutoTradeEvent::REASON_USER_MANUAL,
    ]);

    $event = BotAutoTradeEvent::where('user_id', $user->id)->first();
    expect($event->reason)->toContain('کاربر خرید و فروش خودکار را از تنظیمات بات خاموش کرد');
    expect($event->actor_label)->toContain('user-off@example.test');

    Event::assertDispatched(BotAutoTradeToggled::class, fn (BotAutoTradeToggled $e) => $e->userId === $user->id && $e->enabled === false);
});

it('does not record an event when the user submits the same auto-trade value', function () {
    Event::fake([BotAutoTradeToggled::class]);
    $user = makeToggleUser();
    enableAutoTrade($user);

    Sanctum::actingAs($user);

    $this->patchJson('/api/v1/bot/settings', ['auto_trade_enabled' => true])
        ->assertOk()
        ->assertJsonPath('data.auto_trade_enabled', true);

    expect(BotAutoTradeEvent::where('user_id', $user->id)->count())->toBe(0);
    Event::assertNotDispatched(BotAutoTradeToggled::class);
});

it('turns auto-trade off when a TOGGLE_ON order fails all buys', function () {
    $user = makeToggleUser(['email' => 'failed-buys@example.test']);
    enableAutoTrade($user);
    $order = makeAllFailedPendingOrder($user, BotBuyOrchestrator::TRIGGER_TOGGLE_ON);

    app(BotOrderStatusService::class)->finalizeIfAllFailed($order->id);

    expect($order->fresh()->status)->toBe('FAILED');
    expect((bool) BotUserSettings::where('user_id', $user->id)->value('auto_trade_enabled'))->toBeFalse();

    $this->assertDatabaseHas('bot_auto_trade_events', [
        'user_id'     => $user->id,
        'enabled'     => 0,
        'source'      => BotAutoTradeEvent::SOURCE_SYSTEM,
        'reason_code' => BotAutoTradeEvent::REASON_ORDER_ALL_BUYS_FAILED,
    ]);

    $event = BotAutoTradeEvent::where('user_id', $user->id)->first();
    expect($event->reason)->toContain((string) $order->id);
    expect($event->meta['bot_order_id'])->toBe($order->id);
});

it('leaves auto-trade on when a SIGNAL_SCAN order fails all buys', function () {
    $user = makeToggleUser(['email' => 'scan-failed@example.test']);
    enableAutoTrade($user);
    $order = makeAllFailedPendingOrder($user, BotBuyOrchestrator::TRIGGER_SIGNAL_SCAN);

    app(BotOrderStatusService::class)->finalizeIfAllFailed($order->id);

    expect($order->fresh()->status)->toBe('FAILED');
    expect((bool) BotUserSettings::where('user_id', $user->id)->value('auto_trade_enabled'))->toBeTrue();
    expect(BotAutoTradeEvent::where('user_id', $user->id)->count())->toBe(0);
});

it('turns auto-trade off on admin cancel-all and records the admin actor', function () {
    config(['smart-bot.internal_admin_token' => 'test-admin-token']);

    $user = makeToggleUser(['email' => 'cancel-all@example.test']);
    enableAutoTrade($user);

    $this->postJson("/api/internal/bot-admin/users/{$user->id}/cancel-all", [], [
        'X-Internal-Token' => 'test-admin-token',
        'X-Admin-Id'       => '42',
        'X-Admin-Label'    => 'admin@example.test',
    ])
        ->assertOk()
        ->assertJsonPath('auto_trade_disabled', true);

    expect((bool) BotUserSettings::where('user_id', $user->id)->value('auto_trade_enabled'))->toBeFalse();

    $this->assertDatabaseHas('bot_auto_trade_events', [
        'user_id'     => $user->id,
        'enabled'     => 0,
        'source'      => BotAutoTradeEvent::SOURCE_ADMIN,
        'reason_code' => BotAutoTradeEvent::REASON_ADMIN_CANCEL_ALL,
        'actor_id'    => 42,
        'actor_label' => 'admin@example.test',
    ]);

    $event = BotAutoTradeEvent::where('user_id', $user->id)->first();
    expect($event->reason)->toContain('لغو');
    expect($event->reason)->toContain('admin@example.test');
});

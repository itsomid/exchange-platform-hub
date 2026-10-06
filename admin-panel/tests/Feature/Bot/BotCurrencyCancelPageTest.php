<?php

namespace Tests\Feature\Bot;

use App\Http\Middleware\AdminTwoFactorAuth;
use App\Http\Middleware\CheckUserActivation;
use App\Models\Admin;
use App\Models\Bot\BotBuyExecution;
use App\Models\Bot\BotCurrencyCancellation;
use App\Models\Bot\BotOrder;
use App\Models\Bot\BotSellOrder;
use App\Models\Currency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class BotCurrencyCancelPageTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;
    private Currency $currency;

    protected function setUp(): void
    {
        parent::setUp();

        // Auth + bot-management permission stay enforced; only the 2FA / account
        // activation gates (not under test here) are bypassed.
        $this->withoutMiddleware([AdminTwoFactorAuth::class, CheckUserActivation::class]);

        Permission::firstOrCreate(
            ['name' => 'bot-management', 'guard_name' => 'admin'],
            ['persian_name' => 'مدیریت ربات معاملاتی']
        );
        $this->admin = Admin::factory()->create();
        $this->admin->givePermissionTo('bot-management');
        $this->admin->setAttribute('avatar', null); // read by the dashboard layout

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
        BotSellOrder::forceCreate([
            'bot_buy_execution_id' => $execution->id,
            'target_type'          => 'percent',
            'target_value'         => 20,
            'share_percent'        => 100,
            'amount_to_sell'       => '10',
            'status'               => 'OPEN',
        ]);
    }

    public function test_index_lists_bought_coins_with_open_position(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.bot.currency.index'))
            ->assertOk()
            ->assertSee('ZZZ')
            ->assertSee(route('admin.bot.currency.cancel', $this->currency->id), false);
    }

    public function test_cancel_requires_a_reason(): void
    {
        Http::fake();

        $this->actingAs($this->admin, 'admin')
            ->postJson(route('admin.bot.currency.cancel', $this->currency->id), [
                'cancel_on_exchange' => true,
                'sell_on_exchange'   => false,
                'reason'             => '',
            ])
            ->assertStatus(422);

        Http::assertNothingSent();
    }

    public function test_cancel_forwards_admin_choices_to_api_service(): void
    {
        Http::fake(['*' => Http::response(['ok' => true, 'cancellation_id' => 9, 'status' => 'PENDING'])]);

        $this->actingAs($this->admin, 'admin')
            ->postJson(route('admin.bot.currency.cancel', $this->currency->id), [
                'cancel_on_exchange' => true,
                'sell_on_exchange'   => true,
                'reason'             => 'delisted',
            ])
            ->assertOk()
            ->assertJson(['ok' => true, 'cancellation_id' => 9]);

        Http::assertSent(fn (HttpRequest $r) => str_ends_with($r->url(), "/currencies/{$this->currency->id}/cancel")
            && $r['cancel_on_exchange'] === true
            && $r['sell_on_exchange'] === true
            && $r['reason'] === 'delisted');
    }

    public function test_status_endpoint_returns_the_run(): void
    {
        $run = BotCurrencyCancellation::create([
            'currency_id' => $this->currency->id,
            'reason'      => 'delisted',
            'status'      => 'DONE',
        ]);

        $this->actingAs($this->admin, 'admin')
            ->getJson(route('admin.bot.currency.cancellation.status', $run->id))
            ->assertOk()
            ->assertJsonPath('cancellation.finished', true)
            ->assertJsonPath('cancellation.currency_symbol', 'ZZZ');
    }

    public function test_pending_run_whose_job_failed_elsewhere_is_marked_failed(): void
    {
        $run = $this->pendingRun(now()->subMinutes(5));
        DB::table('failed_jobs')->insert([
            'uuid'       => (string) \Illuminate\Support\Str::uuid(),
            'connection' => 'database',
            'queue'      => 'bot-settlement',
            'payload'    => $this->jobPayload($run->id),
            'exception'  => "Exception: Job is incomplete class\n#0 stack",
            'failed_at'  => now(),
        ]);

        $this->actingAs($this->admin, 'admin')
            ->getJson(route('admin.bot.currency.cancellation.status', $run->id))
            ->assertOk()
            ->assertJsonPath('cancellation.status', 'FAILED')
            ->assertJsonPath('cancellation.finished', true);

        $this->assertStringContainsString('Job is incomplete class', $run->fresh()->error);
    }

    public function test_pending_run_still_queued_or_just_created_is_left_alone(): void
    {
        $queued = $this->pendingRun(now()->subMinutes(5));
        DB::table('jobs')->insert([
            'queue'        => 'bot-settlement',
            'payload'      => $this->jobPayload($queued->id),
            'attempts'     => 0,
            'available_at' => now()->timestamp,
            'created_at'   => now()->timestamp,
        ]);
        $fresh = $this->pendingRun(now());

        $this->assertFalse($queued->failIfOrphaned());
        $this->assertFalse($fresh->failIfOrphaned());
        $this->assertSame('PENDING', $queued->fresh()->status);
        $this->assertSame('PENDING', $fresh->fresh()->status);
    }

    private function pendingRun(\DateTimeInterface $createdAt): BotCurrencyCancellation
    {
        $run = BotCurrencyCancellation::create([
            'currency_id' => $this->currency->id,
            'reason'      => 'delisted',
            'status'      => 'PENDING',
        ]);
        $run->forceFill(['created_at' => $createdAt])->save();

        return $run->fresh();
    }

    private function jobPayload(int $id): string
    {
        $class = 'App\\Jobs\\Bot\\CancelCurrencyPositionsJob';

        return json_encode([
            'displayName' => $class,
            'data'        => [
                'commandName' => $class,
                'command'     => 'O:' . strlen($class) . ':"' . $class . '":2:{s:14:"cancellationId";i:' . $id . ';s:5:"queue";s:14:"bot-settlement";}',
            ],
        ]);
    }
}

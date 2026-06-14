<?php

namespace Tests\Unit\Bot;

use App\Models\Bot\BotBuyExecution;
use App\Models\Bot\BotSignal;
use App\Models\Currency;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Mockery;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class BotSignalTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ── Scope: scopeActive ────────────────────────────────────────────────

    public function test_scope_active_adds_is_active_where_clause(): void
    {
        $builder = Mockery::mock(Builder::class);
        $builder->shouldReceive('where')->with('is_active', true)->once()->andReturnSelf();

        $signal = new BotSignal();
        $result = $signal->scopeActive($builder);

        $this->assertSame($builder, $result);
    }

    // ── Scope: scopeEligibleForPrice ──────────────────────────────────────

    public function test_scope_eligible_for_price_adds_floor_and_ceiling_clauses(): void
    {
        $builder = Mockery::mock(Builder::class);
        $builder->shouldReceive('where')->with('floor_price', '<=', 150.0)->once()->andReturnSelf();
        $builder->shouldReceive('where')->with('ceiling_price', '>=', 150.0)->once()->andReturnSelf();

        $signal = new BotSignal();
        $result = $signal->scopeEligibleForPrice($builder, 150.0);

        $this->assertSame($builder, $result);
    }

    public function test_scope_eligible_for_price_uses_provided_price_value(): void
    {
        $price   = 42.5;
        $builder = Mockery::mock(Builder::class);
        $builder->shouldReceive('where')->with('floor_price', '<=', $price)->once()->andReturnSelf();
        $builder->shouldReceive('where')->with('ceiling_price', '>=', $price)->once()->andReturnSelf();

        $signal = new BotSignal();
        $signal->scopeEligibleForPrice($builder, $price);

        // Mockery expectations verified on tearDown
        $this->assertTrue(true);
    }

    // ── Relations (return-type reflection) ────────────────────────────────

    public function test_currency_relation_return_type_is_belongs_to(): void
    {
        $returnType = (string) (new ReflectionMethod(BotSignal::class, 'currency'))->getReturnType();
        $this->assertStringContainsString('BelongsTo', $returnType);
    }

    public function test_buy_executions_relation_return_type_is_has_many(): void
    {
        $returnType = (string) (new ReflectionMethod(BotSignal::class, 'buyExecutions'))->getReturnType();
        $this->assertStringContainsString('HasMany', $returnType);
    }

    // ── Casts ─────────────────────────────────────────────────────────────

    public function test_sell_targets_cast_is_array(): void
    {
        $signal = new BotSignal();
        $signal->setRawAttributes([
            'sell_targets' => json_encode([['trigger' => 20, 'share' => 50]]),
        ]);

        $this->assertIsArray($signal->sell_targets);
        $this->assertSame(20, $signal->sell_targets[0]['trigger']);
    }

    public function test_is_active_cast_is_boolean(): void
    {
        $signal = new BotSignal();
        $signal->setRawAttributes(['is_active' => '1']);

        $this->assertTrue($signal->is_active);
    }

    public function test_max_allocation_percent_field_is_defined(): void
    {
        $signal = new BotSignal();
        $this->assertContains('max_allocation_percent', $signal->getFillable());
    }
}


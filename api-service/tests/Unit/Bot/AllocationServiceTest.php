<?php

use App\Services\Bot\AllocationService;
use App\Services\Bot\WeightCalculatorService;

/**
 * Helpers
 */
function makeAllocator(): AllocationService
{
    return new AllocationService(new WeightCalculatorService());
}

function baseSignal(array $overrides = []): array
{
    return array_merge([
        'signal_id'                     => 1,
        'currency_id'                   => 1,
        'priority'                      => 1,
        'floor_price'                   => '0',
        'ceiling_price'                 => '100',
        'current_price'                 => '50',
        'min_buy_amount_usdt'           => '0',
        'max_allocation_percent'        => '100',
        'sell_orders_count'             => 0,
        'effective_p2p_min_order_value' => '5',
    ], $overrides);
}

/**
 * (a) Single coin with cap=30% → only 30% allocated.
 */
it('caps a single coin at max_allocation_percent', function () {
    $result = makeAllocator()->allocate(
        candidates: [baseSignal(['signal_id' => 1, 'max_allocation_percent' => '30'])],
        balance: '100',
        alpha: '0.15',
    );

    expect($result->allocations)->toHaveCount(1);
    expect($result->allocations[0]['amount'])->toBe('30.00000000');
    expect($result->skipped)->toBeEmpty();
    // 100 - 30 = 70 unallocated remainder.
    expect((float) $result->unallocatedRemainder)->toBe(70.0);
});

/**
 * (b) Two coins, one hits cap → overflow flows to the second.
 */
it('redistributes overflow from capped coin to uncapped coin', function () {
    $result = makeAllocator()->allocate(
        candidates: [
            baseSignal(['signal_id' => 1, 'currency_id' => 1, 'priority' => 1, 'max_allocation_percent' => '30']),
            baseSignal(['signal_id' => 2, 'currency_id' => 2, 'priority' => 2, 'max_allocation_percent' => '100']),
        ],
        balance: '100',
        alpha: '0.15',
    );

    expect($result->allocations)->toHaveCount(2);

    $byId = collect($result->allocations)->keyBy('signal_id');
    expect((float) $byId[1]['amount'])->toBe(30.0);
    // Whatever the higher-weight coin would have gotten above 30 flows to coin 2.
    // bcmath at scale 8 may leave a sub-unit truncation residual; allow ~1e-5.
    expect(abs((float) $byId[2]['amount'] - 70.0))->toBeLessThan(0.0001);
    expect((float) $result->unallocatedRemainder)->toBeLessThan(0.0001);
});

/**
 * (c) D14: signal whose allocation < effective_min is SKIPPED and share is redistributed.
 *
 * Signal X: priority=2, sell_orders_count=4, p2p_min=5 → effective_min = max(5, 4*5) = 20.
 * With B=100 and α=0.15, X receives ~13.04 USDT which is below 20 → SKIPPED.
 * Freed amount flows to survivor Y.
 */
it('skips signals below D14 effective_min and redistributes their share once', function () {
    $result = makeAllocator()->allocate(
        candidates: [
            baseSignal([
                'signal_id'                     => 10,
                'currency_id'                   => 10,
                'priority'                      => 1,
                'min_buy_amount_usdt'           => '5',
                'sell_orders_count'             => 0,
                'effective_p2p_min_order_value' => '5',
            ]),
            baseSignal([
                'signal_id'                     => 20,
                'currency_id'                   => 20,
                'priority'                      => 2,
                'min_buy_amount_usdt'           => '5',
                'sell_orders_count'             => 4,
                'effective_p2p_min_order_value' => '5',
            ]),
        ],
        balance: '100',
        alpha: '0.15',
    );

    expect($result->allocations)->toHaveCount(1);
    expect($result->allocations[0]['signal_id'])->toBe(10);
    // Survivor absorbs (nearly) the full balance, modulo bcmath truncation.
    expect(abs((float) $result->allocations[0]['amount'] - 100.0))->toBeLessThan(0.0001);

    expect($result->skipped)->toHaveCount(1);
    expect($result->skipped[0]['signal_id'])->toBe(20);
    expect($result->skipped[0]['reason'])->toContain('effective_min 20');
    expect((float) $result->skipped[0]['would_have_received'])->toBeLessThan(20.0);
});

/**
 * (d) D14: same signal, but allocation ≥ effective_min → BUYS.
 */
it('keeps signal that meets D14 effective_min', function () {
    // Two equal-priority signals → 50/50 split. effective_min = max(5, 4*5)=20.
    // Each gets 50, which is ≥ 20, so both BUY.
    $result = makeAllocator()->allocate(
        candidates: [
            baseSignal([
                'signal_id'                     => 1,
                'priority'                      => 1,
                'min_buy_amount_usdt'           => '5',
                'sell_orders_count'             => 4,
                'effective_p2p_min_order_value' => '5',
            ]),
            baseSignal([
                'signal_id'                     => 2,
                'currency_id'                   => 2,
                'priority'                      => 1,
                'min_buy_amount_usdt'           => '5',
                'sell_orders_count'             => 4,
                'effective_p2p_min_order_value' => '5',
            ]),
        ],
        balance: '100',
        alpha: '0.15',
    );

    expect($result->skipped)->toBeEmpty();
    expect($result->allocations)->toHaveCount(2);
    foreach ($result->allocations as $alloc) {
        expect((float) $alloc['amount'])->toBe(50.0);
    }
});

/**
 * (e) Coin allocation < min_buy → dropped, share redistributed.
 *
 * B=10 with two equal-priority signals. K = min(2, floor(sqrt(10))) = 2. Each raw
 * allocation = 5. Signal X has min_buy=10 (effective_min=10) → dropped. Y survives.
 */
it('drops coins whose allocation falls below min_buy and redistributes once', function () {
    $result = makeAllocator()->allocate(
        candidates: [
            baseSignal([
                'signal_id'                     => 1,
                'priority'                      => 1,
                'min_buy_amount_usdt'           => '10',
                'sell_orders_count'             => 0,
                'effective_p2p_min_order_value' => '5',
            ]),
            baseSignal([
                'signal_id'                     => 2,
                'currency_id'                   => 2,
                'priority'                      => 1,
                'min_buy_amount_usdt'           => '1',
                'sell_orders_count'             => 0,
                'effective_p2p_min_order_value' => '5',
            ]),
        ],
        balance: '10',
        alpha: '0.15',
    );

    expect($result->skipped)->toHaveCount(1);
    expect($result->skipped[0]['signal_id'])->toBe(1);

    expect($result->allocations)->toHaveCount(1);
    expect($result->allocations[0]['signal_id'])->toBe(2);
    expect((float) $result->allocations[0]['amount'])->toBe(10.0);
});

/**
 * (f) B_max == B_min edge case: all priorities equal → p_i = 0 → equal weights → 50/50.
 */
it('handles B_max == B_min by treating p_i as 0', function () {
    $result = makeAllocator()->allocate(
        candidates: [
            baseSignal(['signal_id' => 1, 'priority' => 5]),
            baseSignal(['signal_id' => 2, 'currency_id' => 2, 'priority' => 5]),
        ],
        balance: '100',
        alpha: '0.15',
    );

    expect($result->allocations)->toHaveCount(2);
    foreach ($result->allocations as $alloc) {
        expect((float) $alloc['amount'])->toBe(50.0);
        // Each selected signal should have a normalized weight of 0.5.
        expect((float) $alloc['snapshot']['normalized_weight'])->toBe(0.5);
        // p_i must be exactly 0 when priorities are equal.
        expect((float) $alloc['snapshot']['p'])->toBe(0.0);
    }
});

/**
 * (g) The cap is measured against the total wallet ($capBase), not just the
 *     balance being distributed, and existing holdings are subtracted. A coin
 *     already at its full 40% share of the wallet gets no more, even though
 *     there is freed balance to distribute.
 *
 *     Scenario: wallet total = 98, ETH already holds 39.2 (its 40% cap), and
 *     the user re-enables the bot with 58.8 freed balance to allocate.
 */
it('skips a currency already at its max_allocation_percent share of the total wallet', function () {
    $result = makeAllocator()->allocate(
        candidates: [baseSignal(['signal_id' => 1, 'currency_id' => 1, 'max_allocation_percent' => '40'])],
        balance: '58.8',
        alpha: '0.15',
        precheckFloorMode: 'multi',
        capBase: '98',
        committedPerCurrency: [1 => '39.2'],
    );

    expect($result->allocations)->toBeEmpty();
    expect($result->skipped)->toHaveCount(1);
    expect($result->skipped[0]['signal_id'])->toBe(1);
    expect($result->skipped[0]['reason'])->toContain('max_allocation_percent');
    // Flagged so the orchestrator can drop it instead of persisting a noisy
    // SKIPPED row in every new order.
    expect($result->skipped[0]['cap_exhausted'])->toBeTrue();
    // Nothing bought → the freed balance stays unallocated.
    expect((float) $result->unallocatedRemainder)->toBe(58.8);
});

/**
 * (h) After the wallet grows (deposit), the same coin gains headroom equal to
 *     the delta up to its new cap and buys only that much.
 *
 *     Scenario: wallet total = 148, ETH already holds 39.2, so its remaining
 *     cap is 148*40% - 39.2 = 20. With 108.8 freed balance it buys exactly 20.
 */
it('allocates only the remaining cap headroom after the wallet grows', function () {
    $result = makeAllocator()->allocate(
        candidates: [baseSignal(['signal_id' => 1, 'currency_id' => 1, 'max_allocation_percent' => '40'])],
        balance: '108.8',
        alpha: '0.15',
        precheckFloorMode: 'multi',
        capBase: '148',
        committedPerCurrency: [1 => '39.2'],
    );

    expect($result->allocations)->toHaveCount(1);
    expect($result->allocations[0]['signal_id'])->toBe(1);
    expect($result->allocations[0]['amount'])->toBe('20.00000000');
    expect($result->skipped)->toBeEmpty();
});

/**
 * (i) Multi-pass refill: money left over after the first K-slice flows to
 *     lower-priority candidates that were outside the initial top-K.
 *
 *     Real-world scenario: B=35 → K=floor(sqrt(35))=5, so only priorities 1-5
 *     enter pass 1. Priority 1 (30% cap) buys 10.5; priorities 2-5 (10% cap →
 *     3.5 each) sit below the 5 USDT effective_min and are skipped. Without
 *     multi-pass the remaining 24.5 stayed idle even though priority 8 (30%
 *     cap, min_buy 2) could absorb 10.5 of it.
 */
it('spends the remainder on lower-priority signals beyond the first top-K slice', function () {
    $signal = fn (int $id, int $priority, string $maxPct, string $minBuy) => baseSignal([
        'signal_id'                     => $id,
        'currency_id'                   => $id,
        'priority'                      => $priority,
        'min_buy_amount_usdt'           => $minBuy,
        'max_allocation_percent'        => $maxPct,
        'sell_orders_count'             => 3,
        'effective_p2p_min_order_value' => '4',
    ]);

    $result = makeAllocator()->allocate(
        candidates: [
            $signal(1, 1, '30', '5'),  // AVAX-like: bought in pass 1
            $signal(2, 2, '10', '5'),  // caps at 3.5 < 5 → never buyable
            $signal(3, 3, '10', '5'),
            $signal(4, 4, '10', '5'),
            $signal(5, 5, '10', '5'),  // last signal inside the pass-1 top-K
            $signal(6, 6, '10', '5'),
            $signal(7, 7, '10', '5'),
            $signal(8, 8, '30', '2'),  // Sonic-like: reachable only via refill passes
        ],
        balance: '35',
        alpha: '0.15',
        precheckFloorMode: 'single',
    );

    $byId = collect($result->allocations)->keyBy('signal_id');

    // Pass 1 buys signal 1 at its 30% cap; later passes reach signal 8.
    expect($byId->keys()->sort()->values()->all())->toBe([1, 8]);
    expect($byId[1]['amount'])->toBe('10.50000000');
    expect($byId[8]['amount'])->toBe('10.50000000');

    // Every 10%-cap coin is structurally unbuyable (3.5 < 5) and stays skipped.
    expect(collect($result->skipped)->pluck('signal_id')->sort()->values()->all())
        ->toBe([2, 3, 4, 5, 6, 7]);

    // Only the structurally unspendable 14 USDT remains.
    expect($result->unallocatedRemainder)->toBe('14.00000000');
});

it('reconciles truncation dust back into allocations when cap room remains', function () {
    $result = makeAllocator()->allocate(
        candidates: [
            baseSignal(['signal_id' => 1, 'priority' => 1]),
            baseSignal(['signal_id' => 2, 'currency_id' => 2, 'priority' => 1]),
            baseSignal(['signal_id' => 3, 'currency_id' => 3, 'priority' => 1]),
        ],
        balance: '200',
        alpha: '0.15',
    );

    expect($result->skipped)->toBeEmpty();
    expect($result->allocations)->toHaveCount(3);
    expect($result->totalAllocated())->toBe('200.00000000');
    expect($result->unallocatedRemainder)->toBe('0.00000000');
});

/**
 * Tiny float prices become scientific notation under (string) cast (e.g. "1.23E-7").
 * BCMath rejects that form — WeightCalculator must normalize before bcsub/bcdiv.
 */
it('handles scientific-notation micro prices without BCMath ValueError', function () {
    $micro = (string) 1.23e-7; // "1.23E-7"
    expect(stripos($micro, 'e'))->not->toBeFalse();

    $weights = (new WeightCalculatorService())->compute(
        candidates: [baseSignal([
            'floor_price'   => '0',
            'ceiling_price' => '0.000001',
            'current_price' => $micro,
        ])],
        balance: '100',
        alpha: '0.15',
    );

    expect($weights['K'])->toBe(1);
    expect($weights['selected'])->toHaveCount(1);
    expect((float) $weights['selected'][0]['q'])->toBeGreaterThan(0);

    $result = makeAllocator()->allocate(
        candidates: [baseSignal([
            'floor_price'   => '0',
            'ceiling_price' => '0.000001',
            'current_price' => $micro,
        ])],
        balance: '100',
        alpha: '0.15',
    );

    expect($result->allocations)->toHaveCount(1);
});

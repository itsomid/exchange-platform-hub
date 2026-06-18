<?php

use App\Services\Bot\TargetCollapseService;

function targets4Equal(): array
{
    return [
        ['trigger' => 20, 'share' => 25, 'type' => 'percent'],
        ['trigger' => 30, 'share' => 25, 'type' => 'percent'],
        ['trigger' => 40, 'share' => 25, 'type' => 'percent'],
        ['trigger' => 50, 'share' => 25, 'type' => 'percent'],
    ];
}

it('does not collapse when every target meets the minimum', function () {
    $svc    = new TargetCollapseService();
    $result = $svc->collapse(20, targets4Equal(), 5);

    expect($result['collapsed'])->toBeFalse();
    expect($result['original_count'])->toBe(4);
    expect($result['effective_count'])->toBe(4);
    expect($result['note'])->toBeNull();
    foreach ($result['final_targets'] as $t) {
        expect((float) $t['amount'])->toBe(5.0);
        expect((float) $t['share'])->toBe(25.0);
    }
});

it('collapses 4→2 when filled is half of minimum coverage', function () {
    $svc    = new TargetCollapseService();
    $result = $svc->collapse(10, targets4Equal(), 5);

    expect($result['collapsed'])->toBeTrue();
    expect($result['original_count'])->toBe(4);
    expect($result['effective_count'])->toBe(2);

    expect((float) $result['final_targets'][0]['amount'])->toBe(5.0);
    expect((float) $result['final_targets'][1]['amount'])->toBe(5.0);
    expect((float) $result['final_targets'][0]['share'])->toBe(50.0);
    expect((float) $result['final_targets'][1]['share'])->toBe(50.0);
    // Conservative: keeps the LOWER trigger of each merged pair.
    expect((float) $result['final_targets'][0]['trigger'])->toBe(20.0);
    expect((float) $result['final_targets'][1]['trigger'])->toBe(40.0);
});

it('collapses 4→1 when filled equals exactly one minimum', function () {
    $svc    = new TargetCollapseService();
    $result = $svc->collapse(5, targets4Equal(), 5);

    expect($result['collapsed'])->toBeTrue();
    expect($result['original_count'])->toBe(4);
    expect($result['effective_count'])->toBe(1);
    expect($result['final_targets'])->toHaveCount(1);
    expect((float) $result['final_targets'][0]['amount'])->toBe(5.0);
    expect((float) $result['final_targets'][0]['share'])->toBe(100.0);
    // Lowest trigger preserved.
    expect((float) $result['final_targets'][0]['trigger'])->toBe(20.0);
    expect($result['note'])->toContain('4→1');
});

it('collapses 3→1 when no pair of equal slices can meet the minimum', function () {
    $svc     = new TargetCollapseService();
    $targets = [
        ['trigger' => 10, 'share' => 33.33, 'type' => 'percent'],
        ['trigger' => 20, 'share' => 33.33, 'type' => 'percent'],
        ['trigger' => 30, 'share' => 33.34, 'type' => 'percent'],
    ];
    $result = $svc->collapse(5, $targets, 5);

    expect($result['collapsed'])->toBeTrue();
    expect($result['effective_count'])->toBe(1);
    expect((float) $result['final_targets'][0]['amount'])->toBe(5.0);
    expect((float) $result['final_targets'][0]['trigger'])->toBe(10.0);
});

it('handles the exact-boundary case where amount == p2p_min', function () {
    // filled=20 with 4×25% → each amount exactly 5, p2p_min=5 → NO collapse.
    $svc = new TargetCollapseService();
    expect($svc->collapse(20, targets4Equal(), 5)['collapsed'])->toBeFalse();

    // filled=19.99... is just below; e.g. one penny under → at least one target < min → collapse.
    $result = $svc->collapse('19.99000000', targets4Equal(), 5);
    expect($result['collapsed'])->toBeTrue();
    expect($result['effective_count'])->toBeLessThan(4);
});

it('returns an empty result for empty sell_targets', function () {
    $svc = new TargetCollapseService();
    $result = $svc->collapse(10, [], 5);

    expect($result['final_targets'])->toBeEmpty();
    expect($result['original_count'])->toBe(0);
    expect($result['effective_count'])->toBe(0);
    expect($result['collapsed'])->toBeFalse();
});

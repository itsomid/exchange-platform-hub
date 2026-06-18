<?php

namespace App\Services\Bot;

/**
 * Pure (Laravel-independent) allocation pipeline.
 *
 * Steps:
 *   1. Use WeightCalculatorService to pick top-K signals and compute weights.
 *   2. Raw allocation: A_i = B * normalized_weight_i.
 *   3. (D4) Iterative cap enforcement: any A_i > B * max_allocation_percent / 100
 *      is clamped to its cap; the overflow is redistributed proportionally to the
 *      raw weights of uncapped signals. Repeats up to K iterations or until
 *      overflow < epsilon (0.01).
 *   4. (D14 Pre-check) For each surviving allocation:
 *         effective_min_i = max(min_buy_amount_usdt_i, order_floor_i)
 *         where order_floor_i depends on $precheckFloorMode:
 *           'multi'  (conservative): sell_orders_count_i × effective_p2p_min_order_value_i
 *           'single' (relaxed):      effective_p2p_min_order_value_i  (single-order floor;
 *                                    SmartCollapse downstream handles merging)
 *      If A_i < effective_min_i the signal is SKIPPED and its share is redistributed
 *      ONCE among the still-allocated signals proportionally to their raw weights
 *      (subject to caps; any overflow ends up in the unallocated remainder).
 *
 * All money math uses BCMath at scale 8.
 *
 * Candidate input shape:
 *   [
 *     'signal_id'                     => int,
 *     'currency_id'                   => int,
 *     'priority'                      => int,
 *     'floor_price'                   => string|float, // D_i
 *     'ceiling_price'                 => string|float, // E_i
 *     'current_price'                 => string|float, // C_i
 *     'min_buy_amount_usdt'           => string|float,
 *     'max_allocation_percent'        => string|float, // 0-100
 *     'sell_orders_count'             => int,
 *     'effective_p2p_min_order_value' => string|float,
 *   ]
 */
class AllocationService
{
    private const SCALE   = 8;
    private const EPSILON = '0.01';

    public function __construct(private readonly WeightCalculatorService $weights) {}

    /**
     * @param array<int, array<string, mixed>> $candidates
     */
    public function allocate(array $candidates, string|float|int $balance, string|float $alpha, string $precheckFloorMode = 'multi'): AllocationResult
    {
        $B = $this->str($balance);

        $weightResult = $this->weights->compute($candidates, $B, $alpha);
        $selected     = $weightResult['selected'];
        $K            = $weightResult['K'];

        if ($K === 0 || bccomp($weightResult['w_sum'], '0', self::SCALE) === 0) {
            return new AllocationResult(
                allocations: [],
                skipped: [],
                unallocatedRemainder: $B,
                snapshot: [
                    'balance'      => $B,
                    'alpha'        => $weightResult['alpha'],
                    'K'            => $K,
                    'w_sum'        => $weightResult['w_sum'],
                    'reason'       => $K === 0 ? 'NO_ELIGIBLE_SIGNALS' : 'ZERO_WEIGHTS',
                    'candidates'   => count($candidates),
                ],
            );
        }

        // --- Step 2: raw allocation + caps
        foreach ($selected as &$s) {
            $s['amount'] = bcmul($B, $s['normalized_weight'], self::SCALE);
            $maxPct      = $this->str($s['max_allocation_percent'] ?? '100');
            $s['cap']    = bcmul($B, bcdiv($maxPct, '100', self::SCALE), self::SCALE);
            $s['capped'] = false;
        }
        unset($s);

        // --- Step 3 (D4): iterative cap + overflow redistribution
        $maxIters = max(1, $K);
        for ($iter = 0; $iter < $maxIters; $iter++) {
            $overflow    = '0';
            $newlyCapped = false;
            foreach ($selected as &$s) {
                if (! $s['capped'] && bccomp($s['amount'], $s['cap'], self::SCALE) > 0) {
                    $overflow      = bcadd($overflow, bcsub($s['amount'], $s['cap'], self::SCALE), self::SCALE);
                    $s['amount']   = $s['cap'];
                    $s['capped']   = true;
                    $newlyCapped   = true;
                }
            }
            unset($s);

            if (! $newlyCapped) {
                break;
            }
            if (bccomp($overflow, self::EPSILON, self::SCALE) <= 0) {
                break;
            }

            $uncappedWSum = '0';
            foreach ($selected as $s) {
                if (! $s['capped']) {
                    $uncappedWSum = bcadd($uncappedWSum, $s['weight'], self::SCALE);
                }
            }
            if (bccomp($uncappedWSum, '0', self::SCALE) === 0) {
                // No room left to redistribute; overflow becomes unallocated.
                break;
            }
            foreach ($selected as &$s) {
                if (! $s['capped']) {
                    $share        = bcdiv(bcmul($overflow, $s['weight'], self::SCALE), $uncappedWSum, self::SCALE);
                    $s['amount']  = bcadd($s['amount'], $share, self::SCALE);
                }
            }
            unset($s);
        }

        // --- Step 4 (D14 Pre-check)
        $freed         = '0';
        $survivorWSum  = '0';
        foreach ($selected as &$s) {
            $minBuy       = $this->str($s['min_buy_amount_usdt'] ?? '0');
            $sellCount    = (int) ($s['sell_orders_count'] ?? 0);
            $p2pMin       = $this->str($s['effective_p2p_min_order_value'] ?? '0');
            $orderFloor   = $precheckFloorMode === 'single'
                ? $p2pMin
                : bcmul((string) $sellCount, $p2pMin, self::SCALE);
            $effectiveMin = bccomp($minBuy, $orderFloor, self::SCALE) >= 0 ? $minBuy : $orderFloor;

            if (bccomp($s['amount'], $effectiveMin, self::SCALE) < 0) {
                $s['_skipped']     = true;
                $s['_skip_reason'] = $precheckFloorMode === 'single'
                    ? sprintf(
                        'Allocation %s below effective_min %s (min_buy=%s, p2p_min=%s)',
                        $s['amount'],
                        $effectiveMin,
                        $minBuy,
                        $p2pMin,
                    )
                    : sprintf(
                        'Allocation %s below effective_min %s (%d targets × %s min)',
                        $s['amount'],
                        $effectiveMin,
                        $sellCount,
                        $p2pMin,
                    );
                $s['_would_have']  = $s['amount'];
                $freed             = bcadd($freed, $s['amount'], self::SCALE);
                $s['amount']       = '0';
            } else {
                $s['_skipped']     = false;
                $survivorWSum      = bcadd($survivorWSum, $s['weight'], self::SCALE);
            }
        }
        unset($s);

        // Redistribute freed amount ONCE among survivors, clamped to caps.
        if (bccomp($freed, '0', self::SCALE) > 0 && bccomp($survivorWSum, '0', self::SCALE) > 0) {
            $remaining = $freed;
            foreach ($selected as &$s) {
                if ($s['_skipped']) {
                    continue;
                }
                $share     = bcdiv(bcmul($freed, $s['weight'], self::SCALE), $survivorWSum, self::SCALE);
                $newAmount = bcadd($s['amount'], $share, self::SCALE);
                if (bccomp($newAmount, $s['cap'], self::SCALE) > 0) {
                    $actuallyAdded = bcsub($s['cap'], $s['amount'], self::SCALE);
                    $s['amount']   = $s['cap'];
                    $remaining     = bcsub($remaining, $actuallyAdded, self::SCALE);
                } else {
                    $s['amount']   = $newAmount;
                    $remaining     = bcsub($remaining, $share, self::SCALE);
                }
            }
            unset($s);
        }

        // --- Build outputs
        $allocations = [];
        $skipped     = [];
        $totalAlloc  = '0';

        foreach ($selected as $s) {
            $snapshot = [
                'bot_signal_id'                 => (int) $s['signal_id'],
                'currency_id'                   => (int) $s['currency_id'],
                'priority'                      => (int) $s['priority'],
                'D'                             => $this->str($s['floor_price']),
                'E'                             => $this->str($s['ceiling_price']),
                'C'                             => $this->str($s['current_price']),
                'min_buy_amount_usdt'           => $this->str($s['min_buy_amount_usdt'] ?? '0'),
                'max_allocation_percent'        => $this->str($s['max_allocation_percent'] ?? '100'),
                'sell_orders_count'             => (int) ($s['sell_orders_count'] ?? 0),
                'effective_p2p_min_order_value' => $this->str($s['effective_p2p_min_order_value'] ?? '0'),
                'q'                             => $s['q'],
                'p'                             => $s['p'],
                'weight'                        => $s['weight'],
                'normalized_weight'             => $s['normalized_weight'],
            ];

            if ($s['_skipped']) {
                $skipped[] = [
                    'signal_id'           => (int) $s['signal_id'],
                    'currency_id'         => (int) $s['currency_id'],
                    'reason'              => $s['_skip_reason'],
                    'would_have_received' => $s['_would_have'],
                    'snapshot'            => $snapshot,
                ];
            } else {
                $allocations[] = [
                    'signal_id'   => (int) $s['signal_id'],
                    'currency_id' => (int) $s['currency_id'],
                    'amount'      => $s['amount'],
                    'weight'      => $s['weight'],
                    'snapshot'    => $snapshot,
                ];
                $totalAlloc = bcadd($totalAlloc, $s['amount'], self::SCALE);
            }
        }

        $unallocated = bcsub($B, $totalAlloc, self::SCALE);

        return new AllocationResult(
            allocations: $allocations,
            skipped: $skipped,
            unallocatedRemainder: $unallocated,
            snapshot: [
                'balance'         => $B,
                'alpha'           => $weightResult['alpha'],
                'K'               => $K,
                'w_sum'           => $weightResult['w_sum'],
                'total_allocated' => $totalAlloc,
                'candidates'      => count($candidates),
            ],
        );
    }

    private function str(string|float|int $v): string
    {
        if (is_string($v)) {
            return $v;
        }

        return number_format((float) $v, self::SCALE, '.', '');
    }
}

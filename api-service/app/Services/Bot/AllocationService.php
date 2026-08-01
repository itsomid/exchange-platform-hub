<?php

namespace App\Services\Bot;

/**
 * Pure (Laravel-independent) allocation pipeline.
 *
 * Steps:
 *   1. Use WeightCalculatorService to pick top-K signals and compute weights.
 *   2. Raw allocation: A_i = B * normalized_weight_i.
 *   3. (D4) Iterative cap enforcement: any A_i above its remaining cap is clamped
 *      to that cap; the overflow is redistributed proportionally to the raw
 *      weights of uncapped signals. Repeats up to K iterations or until overflow
 *      < epsilon (0.01).
 *
 *      The cap is enforced against the TOTAL wallet balance ($capBase, defaults
 *      to $balance for backward compatibility), not just the balance being
 *      allocated in this batch, and the USDT already committed to each currency
 *      ($committedPerCurrency) is subtracted first:
 *
 *          remaining_cap_i = max(0, capBase * max_allocation_percent_i / 100
 *                                     - committed_i)
 *
 *      This guarantees a coin can never be pushed past its max_allocation_percent
 *      of the whole wallet by re-allocating freed balance across repeated buys
 *      (e.g. toggling the bot off/on and re-buying from the remainder each time).
 *   4. (D14 Pre-check) For each surviving allocation:
 *         effective_min_i = max(min_buy_amount_usdt_i, order_floor_i)
 *         where order_floor_i depends on $precheckFloorMode:
 *           'multi'  (conservative): sell_orders_count_i × effective_p2p_min_order_value_i
 *           'single' (relaxed):      effective_p2p_min_order_value_i  (single-order floor;
 *                                    SmartCollapse downstream handles merging)
 *      If A_i < effective_min_i the signal is SKIPPED and its share is redistributed
 *      ONCE among the still-allocated signals proportionally to their raw weights
 *      (subject to caps; any overflow ends up in the unallocated remainder).
 *   5. Multi-pass refill: steps 1-4 form a single pass. While money remains
 *      (> epsilon) and unseen candidates exist, the remainder is re-run through
 *      the same pipeline against the rest of the candidate list, so cash never
 *      sits idle just because the first K-slice couldn't absorb it:
 *        - currencies bought in a pass leave the pool (their spend is added to
 *          $committedPerCurrency so caps keep holding across passes);
 *        - cap-exhausted currencies leave the pool permanently;
 *        - if a pass buys NOTHING, every currency it skipped leaves the pool,
 *          letting the next pass reach deeper (lower-priority) signals.
 *      The loop stops when the pool is empty, the remainder is dust, or a pass
 *      can neither buy nor skip anything (K = 0 / zero weights).
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
     * @param string|float|int|null            $capBase              Total wallet balance the max_allocation_percent
     *                                                                caps are measured against. Defaults to $balance.
     * @param array<int, string|float|int>     $committedPerCurrency currency_id => USDT already committed to that
     *                                                                currency (open positions + in-flight buys),
     *                                                                subtracted from each cap.
     */
    public function allocate(
        array $candidates,
        string|float|int $balance,
        string|float $alpha,
        string $precheckFloorMode = 'multi',
        string|float|int|null $capBase = null,
        array $committedPerCurrency = [],
    ): AllocationResult {
        $B       = $this->str($balance);
        $capBase = $capBase === null ? $B : $this->str($capBase);

        if (empty($candidates)) {
            return $this->allocatePass($candidates, $B, $alpha, $precheckFloorMode, $capBase, $committedPerCurrency);
        }

        $pool      = array_values($candidates);
        $remaining = $B;
        $committed = [];
        foreach ($committedPerCurrency as $currencyId => $amount) {
            $committed[(int) $currencyId] = $this->str($amount);
        }

        $allocations       = [];
        $skippedByCurrency = [];
        $passes            = 0;
        $firstSnapshot     = null;

        while (! empty($pool) && bccomp($remaining, self::EPSILON, self::SCALE) > 0) {
            $pass = $this->allocatePass($pool, $remaining, $alpha, $precheckFloorMode, $capBase, $committed);
            $passes++;
            $firstSnapshot ??= $pass->snapshot;

            // K = 0 (remainder too small) or zero weights: nothing further can happen.
            if (empty($pass->allocations) && empty($pass->skipped)) {
                break;
            }

            $removed = [];

            foreach ($pass->allocations as $alloc) {
                $allocations[] = $alloc;
                $currencyId    = (int) $alloc['currency_id'];
                // Count this pass's spend against the currency's cap and retire
                // it from the pool so later passes can't buy it twice.
                $committed[$currencyId] = bcadd($committed[$currencyId] ?? '0', $alloc['amount'], self::SCALE);
                $removed[$currencyId]   = true;
                // Bought after all — an earlier-pass skip record is obsolete.
                unset($skippedByCurrency[$currencyId]);
            }

            foreach ($pass->skipped as $skip) {
                $currencyId = (int) $skip['currency_id'];
                $skippedByCurrency[$currencyId] = $skip;
                // Cap-exhausted coins can never absorb more; drop them for good.
                // When the pass bought nothing at all, drop every skipped coin
                // too — otherwise the next pass would re-select the same
                // unbuyable set and deeper (lower-priority) signals that COULD
                // absorb the remainder would never be reached.
                if (! empty($skip['cap_exhausted']) || empty($pass->allocations)) {
                    $removed[$currencyId] = true;
                }
            }

            $pool = array_values(array_filter(
                $pool,
                fn ($c) => ! isset($removed[(int) $c['currency_id']]),
            ));
            $remaining = $pass->unallocatedRemainder;
        }

        $totalAlloc = '0';
        foreach ($allocations as $alloc) {
            $totalAlloc = bcadd($totalAlloc, $alloc['amount'], self::SCALE);
        }

        return new AllocationResult(
            allocations: $allocations,
            skipped: array_values($skippedByCurrency),
            unallocatedRemainder: bcsub($B, $totalAlloc, self::SCALE),
            snapshot: [
                'balance'         => $B,
                'alpha'           => $firstSnapshot['alpha'] ?? $this->str($alpha),
                'K'               => $firstSnapshot['K'] ?? 0,
                'w_sum'           => $firstSnapshot['w_sum'] ?? '0',
                'passes'          => $passes,
                'total_allocated' => $totalAlloc,
                'candidates'      => count($candidates),
            ],
        );
    }

    /**
     * A single pass of the steps 1-4 pipeline (top-K selection, raw allocation,
     * cap enforcement, D14 pre-check + one-shot redistribution).
     *
     * @param array<int, array<string, mixed>> $candidates
     * @param array<int, string|float|int>     $committedPerCurrency
     */
    private function allocatePass(
        array $candidates,
        string $balance,
        string|float $alpha,
        string $precheckFloorMode,
        string $capBase,
        array $committedPerCurrency,
    ): AllocationResult {
        $B = $balance;

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
            $s['amount']    = bcmul($B, $s['normalized_weight'], self::SCALE);
            $maxPct         = $this->str($s['max_allocation_percent'] ?? '100');
            // Cap measured against the TOTAL wallet balance, then reduced by
            // what this currency already holds so its share of the whole
            // wallet never exceeds max_allocation_percent across repeated buys.
            $absoluteCap    = bcmul($capBase, bcdiv($maxPct, '100', self::SCALE), self::SCALE);
            $committed      = $this->str($committedPerCurrency[(int) $s['currency_id']] ?? '0');
            $remainingCap   = bcsub($absoluteCap, $committed, self::SCALE);
            if (bccomp($remainingCap, '0', self::SCALE) < 0) {
                $remainingCap = '0';
            }
            $s['cap']       = $remainingCap;
            $s['capped']    = false;
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

            // A currency whose remaining cap is exhausted (already holding its
            // full max_allocation_percent share of the wallet) can't take any
            // more, so it is skipped with a distinct reason rather than the
            // misleading "below effective_min" note.
            $capExhausted = bccomp($s['cap'], '0', self::SCALE) === 0;

            if ($capExhausted || bccomp($s['amount'], $effectiveMin, self::SCALE) < 0) {
                $s['_skipped']        = true;
                $s['_cap_exhausted']  = $capExhausted;
                $s['_skip_reason'] = $capExhausted
                    ? sprintf(
                        'Currency already at max_allocation_percent (%s%%) of wallet; no remaining headroom',
                        $this->str($s['max_allocation_percent'] ?? '100'),
                    )
                    : ($precheckFloorMode === 'single'
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
                        ));
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

        $this->reconcileRoundingRemainder($selected, $B);

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
                    // Cap-exhausted skips (coin already at its max_allocation_percent
                    // share of the wallet) are not persisted as order rows — they'd
                    // otherwise reappear in every new order as noise.
                    'cap_exhausted'       => (bool) ($s['_cap_exhausted'] ?? false),
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
        if (is_int($v)) {
            return (string) $v;
        }

        if (is_string($v)) {
            $v = trim($v);
            if ($v === '' || ! is_numeric($v)) {
                return '0';
            }
            // PHP casts of tiny floats become "1.23E-8"; BCMath rejects that form.
            if (stripos($v, 'e') !== false) {
                return number_format((float) $v, self::SCALE, '.', '');
            }

            return $v;
        }

        return number_format((float) $v, self::SCALE, '.', '');
    }

    /**
     * Fold any truncation dust back into surviving allocations that still have cap room
     * so persisted wallet locks match the intended allocatable balance.
     *
     * @param array<int, array<string, mixed>> $selected
     */
    private function reconcileRoundingRemainder(array &$selected, string $balance): void
    {
        $allocated = '0';
        foreach ($selected as $s) {
            if (! ($s['_skipped'] ?? false)) {
                $allocated = bcadd($allocated, $s['amount'], self::SCALE);
            }
        }

        $remainder = bcsub($balance, $allocated, self::SCALE);
        if (bccomp($remainder, '0', self::SCALE) <= 0) {
            return;
        }

        for ($index = count($selected) - 1; $index >= 0 && bccomp($remainder, '0', self::SCALE) > 0; $index--) {
            if ($selected[$index]['_skipped'] ?? false) {
                continue;
            }

            $capacity = bcsub($selected[$index]['cap'], $selected[$index]['amount'], self::SCALE);
            if (bccomp($capacity, '0', self::SCALE) <= 0) {
                continue;
            }

            $topUp = bccomp($remainder, $capacity, self::SCALE) === 1
                ? $capacity
                : $remainder;

            $selected[$index]['amount'] = bcadd($selected[$index]['amount'], $topUp, self::SCALE);
            $remainder = bcsub($remainder, $topUp, self::SCALE);
        }
    }
}

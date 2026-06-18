<?php

namespace App\Services\Bot;

/**
 * Pure (Laravel-independent) "smart collapse" of sell targets when each
 * target's amount would fall below the configured P2P minimum order value.
 *
 * Algorithm (per Implementation_plan_v3.md §Phase 5 §D14):
 *   1. amount_i = filled_amount × share_i / 100
 *   2. while min(amount_i) < p2p_min AND len(targets) > 1:
 *        find the adjacent pair with the smallest combined amount and
 *        merge them (share := sum, trigger := the LOWER of the two,
 *        target_type := the lower target's type — conservative: sell earlier).
 *   3. if a single remaining target's amount < p2p_min the caller treats it
 *      as an execution-level failure (defensive — D14 pre-check should have
 *      prevented this).
 *
 * All amount math uses BCMath at scale 8.
 *
 * Input target shape:
 *   ['trigger' => string|float|int,
 *    'share'   => string|float|int,    // percentage (0–100)
 *    'type'    => 'percent'|'price'    // optional, defaults to 'percent']
 */
class TargetCollapseService
{
    private const SCALE = 8;

    /**
     * @param array<int, array<string, mixed>> $sellTargets
     * @return array{
     *     final_targets: array<int, array{trigger:string, share:string, amount:string, type:string}>,
     *     collapsed: bool,
     *     original_count: int,
     *     effective_count: int,
     *     note: ?string
     * }
     */
    public function collapse(string|float|int $filledAmount, array $sellTargets, string|float|int $p2pMin): array
    {
        $filled = $this->str($filledAmount);
        $min    = $this->str($p2pMin);

        if (empty($sellTargets)) {
            return [
                'final_targets'   => [],
                'collapsed'       => false,
                'original_count'  => 0,
                'effective_count' => 0,
                'note'            => null,
            ];
        }

        // Normalise + sort by trigger ascending so neighbours are well-defined.
        $targets = array_map(function (array $t): array {
            return [
                'trigger' => $this->str($t['trigger']),
                'share'   => $this->str($t['share']),
                'type'    => isset($t['type']) ? (string) $t['type'] : 'percent',
            ];
        }, $sellTargets);

        usort($targets, fn ($a, $b) => bccomp($a['trigger'], $b['trigger'], self::SCALE));

        $originalCount = count($targets);
        $collapsed     = false;

        $recomputeAmounts = function (array &$targets) use ($filled): void {
            foreach ($targets as &$t) {
                $t['amount'] = bcdiv(bcmul($filled, $t['share'], self::SCALE), '100', self::SCALE);
            }
            unset($t);
        };

        $recomputeAmounts($targets);

        // Iteratively merge while any amount is below p2p_min and we still have > 1 target.
        // Bounded by the original count (each iteration reduces target count by 1).
        for ($i = 0; $i < $originalCount; $i++) {
            if (count($targets) <= 1) {
                break;
            }
            // Find the minimum amount; if all ≥ p2pMin we're done.
            $minAmount = $targets[0]['amount'];
            foreach ($targets as $t) {
                if (bccomp($t['amount'], $minAmount, self::SCALE) < 0) {
                    $minAmount = $t['amount'];
                }
            }
            if (bccomp($minAmount, $min, self::SCALE) >= 0) {
                break;
            }

            // Find adjacent pair with smallest combined amount (leftmost wins on ties).
            $bestIdx = 0;
            $bestSum = bcadd($targets[0]['amount'], $targets[1]['amount'], self::SCALE);
            for ($k = 1; $k < count($targets) - 1; $k++) {
                $sum = bcadd($targets[$k]['amount'], $targets[$k + 1]['amount'], self::SCALE);
                if (bccomp($sum, $bestSum, self::SCALE) < 0) {
                    $bestSum = $sum;
                    $bestIdx = $k;
                }
            }

            // Merge bestIdx with bestIdx+1: keep LOWER trigger + lower's type (conservative).
            $merged = [
                'trigger' => $targets[$bestIdx]['trigger'],
                'share'   => bcadd($targets[$bestIdx]['share'], $targets[$bestIdx + 1]['share'], self::SCALE),
                'type'    => $targets[$bestIdx]['type'],
            ];
            array_splice($targets, $bestIdx, 2, [$merged]);
            $recomputeAmounts($targets);
            $collapsed = true;
        }

        $effectiveCount = count($targets);
        $note = $collapsed
            ? sprintf('Targets collapsed %d→%d due to p2p_min_order_value=%s', $originalCount, $effectiveCount, $min)
            : null;

        return [
            'final_targets'   => $targets,
            'collapsed'       => $collapsed,
            'original_count'  => $originalCount,
            'effective_count' => $effectiveCount,
            'note'            => $note,
        ];
    }

    private function str(string|float|int $v): string
    {
        if (is_string($v)) {
            return $v;
        }
        return number_format((float) $v, self::SCALE, '.', '');
    }
}

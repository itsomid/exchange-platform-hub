<?php

namespace App\Services\Bot;

/**
 * Pure (Laravel-independent) weight calculator implementing the
 * specification in docs/فرمول_نهایی_اختصاص_سرمایه_و_وزن_دهی.md.
 *
 * All money math uses BCMath (scale 8).
 *
 * Input shape (per candidate):
 *   [
 *     'signal_id'     => int,
 *     'currency_id'   => int,
 *     'priority'      => int,
 *     'floor_price'   => string|float,  // D_i
 *     'ceiling_price' => string|float,  // E_i
 *     'current_price' => string|float,  // C_i
 *     // ...any extra keys are passed through into the snapshot
 *   ]
 *
 * Output:
 *   [
 *     'K'         => int,
 *     'alpha'     => string,
 *     'selected'  => array<int, array{...input, q: string, p: string, raw_weight: string, weight: string, normalized_weight: string}>,
 *     'w_sum'     => string,
 *   ]
 */
class WeightCalculatorService
{
    private const SCALE = 8;

    /**
     * @param array<int, array<string, mixed>> $candidates
     * @param string|float|int                 $balance Total USDT to allocate.
     * @param string|float                     $alpha
     * @return array<string, mixed>
     */
    public function compute(array $candidates, string|float|int $balance, string|float $alpha): array
    {
        $B     = $this->str($balance);
        $alpha = $this->str($alpha);

        // --- Step 3: K = min(|F|, floor(sqrt(B)))
        $sqrtB     = bcsqrt($B, self::SCALE);
        $floorSqrt = (int) explode('.', $sqrtB)[0];
        $K         = min(count($candidates), $floorSqrt);

        if ($K <= 0 || empty($candidates)) {
            return [
                'K'        => 0,
                'alpha'    => $alpha,
                'selected' => [],
                'w_sum'    => '0',
            ];
        }

        // --- Step 4: select top K by priority (lower number = higher priority)
        usort($candidates, fn ($a, $b) => ((int) $a['priority']) <=> ((int) $b['priority']));
        $selected = array_slice($candidates, 0, $K);

        // --- Step 5: p_i normalization
        $priorities = array_map(fn ($c) => (int) $c['priority'], $selected);
        $bMin       = min($priorities);
        $bMax       = max($priorities);
        $priorityRange = $bMax - $bMin;

        // --- Steps 6–8: compute q_i, p_i, W_i, and W_sum
        $wSum = '0';
        foreach ($selected as &$s) {
            $D = $this->str($s['floor_price']);
            $E = $this->str($s['ceiling_price']);
            $C = $this->str($s['current_price']);

            // q_i = (E - C) / (E - D)
            $denomQ = bcsub($E, $D, self::SCALE);
            $q = bccomp($denomQ, '0', self::SCALE) === 0
                ? '0'
                : bcdiv(bcsub($E, $C, self::SCALE), $denomQ, self::SCALE);

            // p_i = (B_i - B_min) / (B_max - B_min); 0 if range is 0
            if ($priorityRange === 0) {
                $p = '0';
            } else {
                $p = bcdiv((string) ((int) $s['priority'] - $bMin), (string) $priorityRange, self::SCALE);
            }

            // W_i = q_i * (1 + (alpha - 1) * p_i)
            $priorityFactor = bcadd('1', bcmul(bcsub($alpha, '1', self::SCALE), $p, self::SCALE), self::SCALE);
            $rawWeight      = bcmul($q, $priorityFactor, self::SCALE);

            // Guard against negative weights (e.g., C > E shouldn't happen post-filter, but be safe).
            if (bccomp($rawWeight, '0', self::SCALE) < 0) {
                $rawWeight = '0';
            }

            $s['q']          = $q;
            $s['p']          = $p;
            $s['raw_weight'] = $rawWeight;
            $s['weight']     = $rawWeight;

            $wSum = bcadd($wSum, $rawWeight, self::SCALE);
        }
        unset($s);

        // --- Step 9: normalize
        foreach ($selected as &$s) {
            $s['normalized_weight'] = bccomp($wSum, '0', self::SCALE) === 0
                ? '0'
                : bcdiv($s['weight'], $wSum, self::SCALE);
        }
        unset($s);

        return [
            'K'        => $K,
            'alpha'    => $alpha,
            'selected' => $selected,
            'w_sum'    => $wSum,
        ];
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

        // number_format with 8 decimals, no thousands sep (avoids scientific notation).
        return number_format((float) $v, self::SCALE, '.', '');
    }
}

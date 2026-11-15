<?php

namespace App\Services\Bot;

/**
 * Reconstructs the price a settlement was booked at.
 *
 * bot_trade_settlements stores gross_revenue = amount * fill_price, truncated
 * to 8 decimals, and does not store the price itself. Dividing gross revenue
 * by the sell amount and rounding back to 8 decimals recovers that price.
 * Cancel always books amount_to_sell; do not use this for a partial fill
 * whose filled amount differs from amount_to_sell.
 */
final class BotSettlementQuote
{
    private const SCALE = 8;

    public static function targetPrice(?string $targetType, ?string $targetValue, ?string $avgBuyPrice): ?string
    {
        $trigger = self::num($targetValue);
        if ($trigger === null) {
            return null;
        }

        if (strtolower(trim((string) $targetType)) === 'price') {
            return self::round($trigger, self::SCALE);
        }

        $avg = self::num($avgBuyPrice);
        if ($avg === null || bccomp($avg, '0', self::SCALE) <= 0) {
            return null;
        }

        $factor = bcadd('1', bcdiv($trigger, '100', 12), 12);

        return self::round(bcmul($avg, $factor, 12), self::SCALE);
    }

    /**
     * @return array{
     *     fill_price: string,
     *     target_price: ?string,
     *     distance_amount: ?string,
     *     distance_pct: ?string,
     *     progress_pct: ?string,
     *     move_pct: ?string
     * }|null
     */
    public static function fromAmounts(
        ?string $grossRevenue,
        ?string $amountToSell,
        ?string $avgBuyPrice,
        ?string $targetType,
        ?string $targetValue,
    ): ?array {
        $gross = self::num($grossRevenue);
        $amount = self::num($amountToSell);
        if (
            $gross === null || $amount === null
            || bccomp($gross, '0', self::SCALE) <= 0
            || bccomp($amount, '0', self::SCALE) <= 0
        ) {
            return null;
        }

        $fill = self::round(bcdiv($gross, $amount, 16), self::SCALE);
        if (bccomp($fill, '0', self::SCALE) <= 0) {
            return null;
        }

        $avg = self::num($avgBuyPrice);
        $target = self::targetPrice($targetType, $targetValue, $avg);

        $distanceAmount = null;
        $distancePct = null;
        $progressPct = null;
        if ($target !== null) {
            $distanceAmount = bcsub($target, $fill, self::SCALE);
            $distancePct = self::round(bcdiv(bcmul($distanceAmount, '100', 12), $fill, 12), 4);
            if ($avg !== null && bccomp($avg, '0', self::SCALE) > 0) {
                $span = bcsub($target, $avg, self::SCALE);
                if (bccomp($span, '0', self::SCALE) !== 0) {
                    $moved = bcsub($fill, $avg, self::SCALE);
                    $progressPct = self::round(bcdiv(bcmul($moved, '100', 12), $span, 12), 4);
                }
            }
        }

        $movePct = null;
        if ($avg !== null && bccomp($avg, '0', self::SCALE) > 0) {
            $movePct = self::round(bcdiv(bcmul(bcsub($fill, $avg, self::SCALE), '100', 12), $avg, 12), 4);
        }

        return [
            'fill_price' => $fill,
            'target_price' => $target,
            'distance_amount' => $distanceAmount,
            'distance_pct' => $distancePct,
            'progress_pct' => $progressPct,
            'move_pct' => $movePct,
        ];
    }

    private static function num(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $number = trim((string) $value);
        if ($number === '' || ! is_numeric($number)) {
            return null;
        }

        if (stripos($number, 'e') !== false) {
            $number = number_format((float) $number, 16, '.', '');
        }

        return $number;
    }

    private static function round(string $number, int $scale): string
    {
        $negative = str_starts_with($number, '-');
        $absolute = $negative ? substr($number, 1) : $number;
        if (! str_contains($absolute, '.')) {
            $absolute .= '.';
        }

        [$integer, $decimals] = explode('.', $absolute, 2);
        $decimals = str_pad($decimals, $scale + 1, '0');
        $base = ($integer === '' ? '0' : $integer).'.'.substr($decimals, 0, $scale);
        if ((int) $decimals[$scale] >= 5) {
            $step = $scale === 0 ? '1' : '0.'.str_repeat('0', $scale - 1).'1';
            $base = bcadd($base, $step, $scale);
        } else {
            $base = bcadd($base, '0', $scale);
        }

        return $negative ? '-'.$base : $base;
    }
}

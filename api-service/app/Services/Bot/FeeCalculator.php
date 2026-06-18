<?php

namespace App\Services\Bot;

use App\Exceptions\Bot\BotTransferAmountTooLowException;

/**
 * Transfer fee model (per RFP D6):
 *   amount < 20            → exception (rejected)
 *   20 ≤ amount ≤ 100     → 1 USDT flat
 *   100 < amount ≤ 1000   → 1% of amount
 *   amount > 1000          → 12 USDT flat
 *
 * The same schedule applies to both transferIn and transferOut.
 */
class FeeCalculator
{
    private const SCALE = 8;

    public function transferFee(string $amount): string
    {
        return $this->calculate($amount);
    }

    public function withdrawFee(string $amount): string
    {
        return $this->calculate($amount);
    }

    /**
     * Cancel fee charged when a user cancels a bot order before all sell legs fill.
     * Same schedule as transfer fee but without the 20-USDT minimum (cancels of
     * tiny remaining amounts are still allowed).
     */
    public function cancelFee(string $remainingCostBasis): string
    {
        if (bccomp($remainingCostBasis, '0', self::SCALE) <= 0) {
            return '0.00000000';
        }
        if (bccomp($remainingCostBasis, '1000', self::SCALE) === 1) {
            return '12.00000000';
        }
        if (bccomp($remainingCostBasis, '100', self::SCALE) === 1) {
            return bcdiv($remainingCostBasis, '100', self::SCALE);
        }
        return '1.00000000';
    }

    private function calculate(string $amount): string
    {
        if (bccomp($amount, '20', self::SCALE) === -1) {
            throw new BotTransferAmountTooLowException();
        }

        if (bccomp($amount, '1000', self::SCALE) === 1) {
            return '12.00000000';
        }

        if (bccomp($amount, '100', self::SCALE) === 1) {
            // 1% of amount
            return bcmul(bcdiv($amount, '100', self::SCALE), '1', self::SCALE);
        }

        // 20 ≤ amount ≤ 100
        return '1.00000000';
    }
}

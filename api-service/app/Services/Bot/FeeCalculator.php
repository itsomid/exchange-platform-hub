<?php

namespace App\Services\Bot;

use App\Exceptions\Bot\BotTransferAmountTooLowException;
use App\Models\Bot\BotGlobalSettings;

/**
 * Transfer fee is resolved from bot_global_settings.transfer_fee_tiers.
 * The same schedule applies to transferIn, transferOut, and cancelFee
 * (cancelFee skips the minimum-deposit check).
 */
class FeeCalculator
{
    private const SCALE = 8;

    public function transferFee(string $amount): string
    {
        return $this->calculate($amount, enforceMinimum: true);
    }

    public function withdrawFee(string $amount): string
    {
        return $this->calculate($amount, enforceMinimum: true);
    }

    /**
     * Net USDT that lands in the bot wallet when transferring exactly
     * min_deposit_usdt. Buy triggers must use this (not the gross minimum),
     * otherwise a valid minimum deposit can never start trading after the
     * transfer fee is deducted.
     */
    public function minNetDeposit(): string
    {
        $minDeposit = (string) BotGlobalSettings::current()->min_deposit_usdt;
        $fee = $this->transferFee($minDeposit);

        return bcsub($minDeposit, $fee, self::SCALE);
    }

    /**
     * Cancel fee charged when a user cancels a bot order before all sell legs fill.
     * Same tier schedule as transfer fee but without the minimum-deposit check.
     */
    public function cancelFee(string $remainingCostBasis): string
    {
        if (bccomp($remainingCostBasis, '0', self::SCALE) <= 0) {
            return '0.00000000';
        }

        return $this->calculate($remainingCostBasis, enforceMinimum: false);
    }

    private function calculate(string $amount, bool $enforceMinimum): string
    {
        $settings = BotGlobalSettings::current();
        $minDeposit = (string) $settings->min_deposit_usdt;

        if ($enforceMinimum && bccomp($amount, $minDeposit, self::SCALE) === -1) {
            throw new BotTransferAmountTooLowException();
        }

        $tier = $this->findTier($amount, $settings->resolvedTransferFeeTiers());

        if ($tier === null) {
            throw new BotTransferAmountTooLowException();
        }

        return $this->feeForTier($amount, $tier);
    }

    /** @param list<array{from: mixed, to: mixed, fee_type: string, fee_value: mixed}> $tiers */
    private function findTier(string $amount, array $tiers): ?array
    {
        foreach ($tiers as $tier) {
            $from = (string) $tier['from'];
            $to = isset($tier['to']) && $tier['to'] !== null && $tier['to'] !== ''
                ? (string) $tier['to']
                : null;

            if (bccomp($amount, $from, self::SCALE) === -1) {
                continue;
            }

            if ($to !== null && bccomp($amount, $to, self::SCALE) === 1) {
                continue;
            }

            return $tier;
        }

        return null;
    }

    /** @param array{from: mixed, to: mixed, fee_type: string, fee_value: mixed} $tier */
    private function feeForTier(string $amount, array $tier): string
    {
        $feeValue = (string) $tier['fee_value'];

        if ($tier['fee_type'] === 'percent') {
            return bcmul(bcdiv($amount, '100', self::SCALE), $feeValue, self::SCALE);
        }

        return bcadd($feeValue, '0', self::SCALE);
    }
}

<?php

namespace App\Services\Exchanges\Asset\Binance;

use App\Enums\SpotStatusEnum;

class BinanceOrderFormatter
{
    public static function normalizeSymbol(string $market): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $market) ?? '');
    }

    /**
     * Binance rejects quantities that are not a multiple of the lot step or that
     * contain thousand separators, so floor to the step and print a plain decimal.
     */
    public static function formatQuantity(string $quantity, ?string $stepSize, int $fallbackPrecision = 8): string
    {
        $quantity = self::plainDecimal($quantity);

        if ($stepSize === null) {
            return self::trimZeros(bcadd($quantity, '0', max(0, $fallbackPrecision)));
        }

        $steps = bcdiv($quantity, $stepSize, 0);

        return self::trimZeros(bcmul($steps, $stepSize, self::decimalPlaces($stepSize)));
    }

    /**
     * MARKET orders are bound by MARKET_LOT_SIZE, whose step is often reported as
     * zero, meaning LOT_SIZE applies.
     *
     * @param  array<string, string>  $filters
     */
    public static function stepFor(string $orderType, array $filters): ?string
    {
        if ($orderType === 'MARKET' && self::isPositive($filters['marketStepSize'] ?? null)) {
            return self::plainDecimal($filters['marketStepSize']);
        }

        return self::isPositive($filters['stepSize'] ?? null) ? self::plainDecimal($filters['stepSize']) : null;
    }

    /**
     * @param  array<string, string>  $filters
     */
    public static function minQtyFor(string $orderType, array $filters): ?string
    {
        if ($orderType === 'MARKET' && self::isPositive($filters['marketMinQty'] ?? null)) {
            return self::plainDecimal($filters['marketMinQty']);
        }

        return self::isPositive($filters['minQty'] ?? null) ? self::plainDecimal($filters['minQty']) : null;
    }

    /**
     * Minimum quantity × price (in the quote asset), or null when the symbol has no
     * NOTIONAL filter or it does not apply to MARKET orders.
     *
     * @param  array<string, string>  $filters
     */
    public static function minNotionalFor(string $orderType, array $filters): ?string
    {
        if (! self::isPositive($filters['minNotional'] ?? null)) {
            return null;
        }

        if ($orderType === 'MARKET' && ($filters['minNotionalAppliesToMarket'] ?? '1') !== '1') {
            return null;
        }

        return self::plainDecimal($filters['minNotional']);
    }

    public static function mapError(int $code, string $message): SpotStatusEnum
    {
        $message = strtolower($message);

        if (str_contains($message, 'insufficient balance')) {
            return SpotStatusEnum::NotEnoughBalance;
        }

        if (str_contains($message, 'percent_price')) {
            return SpotStatusEnum::PriceDifferenceTooLarge;
        }

        if ($code === -1013 && (str_contains($message, 'lot_size') || str_contains($message, 'notional'))) {
            return SpotStatusEnum::AmountTooSmall;
        }

        return SpotStatusEnum::BuyOrderFailed;
    }

    /**
     * Sums the commission of the fills. When fills were charged in more than one
     * asset (BNB ran out mid-order), the order's own base/quote asset wins because
     * that is the one that changes the traded balances.
     *
     * @param  array<int, array<string, mixed>>  $fills
     * @param  array<int, string>  $preferredAssets
     * @return array{amount: string, currency: ?string}
     */
    public static function commission(array $fills, array $preferredAssets = []): array
    {
        $byAsset = [];
        foreach ($fills as $fill) {
            $asset = strtoupper((string) ($fill['commissionAsset'] ?? ''));
            if ($asset === '') {
                continue;
            }
            $byAsset[$asset] = bcadd($byAsset[$asset] ?? '0', self::plainDecimal((string) ($fill['commission'] ?? '0')), 8);
        }

        if ($byAsset === []) {
            return ['amount' => '0', 'currency' => null];
        }

        $currency = array_key_first($byAsset);
        foreach ($preferredAssets as $asset) {
            if (isset($byAsset[strtoupper($asset)])) {
                $currency = strtoupper($asset);
                break;
            }
        }

        return ['amount' => self::trimZeros($byAsset[$currency]), 'currency' => $currency];
    }

    public static function plainDecimal(string $number): string
    {
        $number = str_replace([',', ' '], '', trim($number));

        if (! is_numeric($number)) {
            return '0';
        }

        if (stripos($number, 'e') !== false) {
            $number = sprintf('%.18F', (float) $number);
        }

        return self::trimZeros($number);
    }

    private static function isPositive(?string $value): bool
    {
        return $value !== null && bccomp(self::plainDecimal($value), '0', 18) === 1;
    }

    private static function decimalPlaces(string $number): int
    {
        $number = self::trimZeros($number);
        $dot = strpos($number, '.');

        return $dot === false ? 0 : strlen($number) - $dot - 1;
    }

    private static function trimZeros(string $number): string
    {
        if (! str_contains($number, '.')) {
            return $number;
        }

        $number = rtrim(rtrim($number, '0'), '.');

        return $number === '' || $number === '-' ? '0' : $number;
    }
}

<?php

namespace App\Services\Bot\ReferenceExchange;

/**
 * Normalizes a CoinEx spot order fill into bot-facing amounts.
 *
 * Buy fee may be charged in quote (e.g. USDT) or in the base coin (e.g. NEO).
 * When charged in base, CoinEx's filled_amount is gross — the fee is deducted
 * from the received coin, so the sellable balance is gross − base_fee.
 *
 * Sell fills are left untouched: settlement needs the sold base amount as-is.
 *
 * `fee_currency` is always the concrete asset symbol (e.g. "NEO", "USDT"),
 * never a generic "base"/"quote" label.
 */
final class CoinExFillResolver
{
    private const SCALE = 8;

    /**
     * @param  array<string,mixed>  $data  CoinEx order `data` payload
     * @return array{
     *     filled_amount: string,
     *     exchange_fee: string,
     *     fee_currency: ?string,
     *     gross_filled: string,
     *     base_fee: string,
     *     quote_fee: string,
     * }
     */
    public static function resolve(
        array $data,
        string $avgPrice,
        ?string $side,
        string $baseSymbol,
        string $quoteSymbol = 'USDT',
    ): array {
        $gross    = (string) ($data['filled_amount'] ?? '0');
        $baseFee  = (string) ($data['base_fee'] ?? '0');
        $quoteFee = (string) ($data['quote_fee'] ?? '0');
        $isBuy    = strtolower((string) $side) === 'buy';
        $base     = strtoupper($baseSymbol);
        $quote    = strtoupper($quoteSymbol);

        [$feeUsdt, $feeCurrency] = self::resolveFee($quoteFee, $baseFee, $avgPrice, $base, $quote);

        $sellable = $gross;
        if (
            $isBuy
            && $feeCurrency !== null
            && $feeCurrency === $base
            && bccomp($baseFee, '0', self::SCALE) > 0
            && bccomp($gross, $baseFee, self::SCALE) >= 0
        ) {
            $sellable = bcsub($gross, $baseFee, self::SCALE);
        }

        return [
            'filled_amount' => $sellable,
            'exchange_fee'  => $feeUsdt,
            'fee_currency'  => $feeCurrency,
            'gross_filled'  => $gross,
            'base_fee'      => $baseFee,
            'quote_fee'     => $quoteFee,
        ];
    }

    /**
     * @return array{0: string, 1: ?string} [fee_usdt, fee_currency_symbol]
     */
    private static function resolveFee(
        string $quoteFee,
        string $baseFee,
        string $avgPrice,
        string $baseSymbol,
        string $quoteSymbol,
    ): array {
        if (bccomp($quoteFee, '0', self::SCALE) > 0) {
            return [$quoteFee, $quoteSymbol];
        }

        if (bccomp($baseFee, '0', self::SCALE) > 0 && bccomp($avgPrice, '0', self::SCALE) > 0) {
            return [bcmul($baseFee, $avgPrice, self::SCALE), $baseSymbol];
        }

        return ['0', null];
    }
}

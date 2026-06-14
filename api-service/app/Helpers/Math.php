<?php

namespace App\Helpers;

class Math
{
    public static function add(string|float|int $number1, string|float|int $number2): string
    {
        return bcadd(
            self::toDecimalString($number1),
            self::toDecimalString($number2),
            config('bitexroom.scale_precision')
        );
    }

    public static function sub(string|float|int $number1, string|float|int $number2): string
    {
        return bcsub(
            self::toDecimalString($number1),
            self::toDecimalString($number2),
            config('bitexroom.scale_precision')
        );
    }

    public static function mul(string|float|int $number1, string|float|int $number2): string
    {
        return bcmul(
            self::toDecimalString($number1),
            self::toDecimalString($number2),
            config('bitexroom.scale_precision')
        );
    }

    public static function div(string|float|int $number1, string|float|int $number2): string
    {
        return bcdiv(
            self::toDecimalString($number1),
            self::toDecimalString($number2),
            config('bitexroom.scale_precision')
        );
    }

    public static function comp(string|float|int $number1, string|float|int $number2): int
    {
        return bccomp(
            self::toDecimalString($number1),
            self::toDecimalString($number2),
            config('bitexroom.scale_precision')
        );
    }

    private static function toDecimalString(string|float|int $number): string
    {
        if (is_int($number)) {
            return (string) $number;
        }

        if (is_string($number)) {
            $trimmed = trim($number);
            if (! is_numeric($trimmed)) {
                return '0';
            }
            // Convert scientific notation (e.g., "3.31E-10") to plain decimal string
            if (stripos($trimmed, 'e') !== false) {
                return number_format((float) $trimmed, 20, '.', '');
            }
            return $trimmed;
        }

        // float: high-precision sprintf to avoid truncating significant digits.
        // Note: prefer passing string for amounts with >15 significant digits.
        return sprintf('%.20f', $number);
    }
}

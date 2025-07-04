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

    private static function toDecimalString(string|float|int $number, int $precision = 8): string
    {
        return sprintf('%.'.$precision.'f', (float) $number);
    }
}

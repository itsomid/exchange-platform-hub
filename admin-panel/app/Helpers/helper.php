<?php

use Carbon\Carbon;
use Illuminate\Support\Arr;
use Morilog\Jalali\Jalalian;

function generatePhone(): string
{
    return '09'.Arr::random(['02', '10', '38', '35', '90', '22', '12', '15', '19']).rand(1000000, 9999999);
}

if (! function_exists('generateComplexPassword')) {
    /**
     * @throws \Random\RandomException
     */
    function generateComplexPassword($length = 8): string
    {
        // Ensure at least one letter and one number
        $letters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $numbers = '0123456789';
        $symbols = '!@#$%&?';
        $allCharacters = $letters.$numbers.$symbols;

        // Start with one letter and one number
        $password = $letters[random_int(0, strlen($letters) - 1)].
            $numbers[random_int(0, strlen($numbers) - 1)];

        // Fill the rest with random characters
        for ($i = 2; $i < $length; $i++) {
            $password .= $allCharacters[random_int(0, strlen($allCharacters) - 1)];
        }

        // Shuffle the password to randomize its order
        return str_shuffle($password);
    }
}

if (! function_exists('formatNumber')) {
    /**
     * Format the number with slashes.
     *
     * @param float $number
     * @return string
     */
    function formatNumber( $number , $decimal = 8, $char =',')
    {
        $number = number_format($number, $decimal, '.', $char); // Format the number with commas


        return str_replace(',', $char, $number); // Replace commas with slashes
    }
}
if (! function_exists('formatNumberTrimZeros')) {
    /**
     * Format a numeric string:
     *   - add thousands separators to integer part
     *   - trim trailing zeros in decimal part
     * e.g. "92789.75000400" => "92,789.750004"
     *
     * @param string|float|int $number   The number to format (passed as string recommended)
     * @param string           $thousand The thousands separator (default ",")
     * @param string           $decimal  The decimal separator (default ".")
     * @return string
     */
    function formatNumberTrimZeros($number, $thousand = ',', $decimal = '.')
    {
        // Convert to string to avoid float rounding issues
        $numberString = (string) $number;

        // Split into integer and decimal parts
        $parts = explode('.', $numberString);
        $integerPart = $parts[0];
        $decimalPart = isset($parts[1]) ? $parts[1] : '';

        // Trim trailing zeros from the decimal part
        $decimalPart = rtrim($decimalPart, '0');

        // Format the integer part with thousands separators
        // (RegEx approach to insert $thousand every 3 digits from right to left)
        $integerPart = preg_replace('/\B(?=(\d{3})+(?!\d))/', $thousand, $integerPart);

        // If decimal part is now empty, just return the integer part
        if ($decimalPart === '') {
            return $integerPart;
        }

        // Otherwise, recombine integer and decimal parts with the desired decimal separator
        return $integerPart . $decimal . $decimalPart;
    }
}
//if (!function_exists('formatNumber')) {
//    function formatNumber($number, $decimals = 2)
//    {
//        return number_format($number, $decimals);
//    }
//}


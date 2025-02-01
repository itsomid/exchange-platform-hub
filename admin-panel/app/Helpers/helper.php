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
    function formatNumber( $number , $decimal = 2, $char =',')
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

        if ($number < 1) {
            // Trim trailing zeros
            $decimalPart = rtrim($decimalPart, '0');
            // Count leading zeros in the decimal part
            $leadingZeros = strspn($decimalPart, '0');

            if ($leadingZeros >= 4) {
                // Keep all digits (preserve significant digits after 4 zeros)
                // No truncation needed since trailing zeros are already trimmed
            } else {
                // Truncate to 4 digits and trim any new trailing zeros
                $decimalPart = substr($decimalPart, 0, 4);
                $decimalPart = rtrim($decimalPart, '0');
            }
        } else {
            // For numbers >= 1, truncate to 2 digits and trim trailing zeros
            $decimalPart = substr($decimalPart, 0, 2);
            $decimalPart = rtrim($decimalPart, '0');
        }

        // Format the integer part with thousands separators
        $integerPart = preg_replace('/\B(?=(\d{3})+(?!\d))/', $thousand, $integerPart);

        // If decimal part is empty, return only the integer part
        if ($decimalPart === '') {
            return $integerPart;
        }

        // Recombine parts with the decimal separator
        return $integerPart . $decimal . $decimalPart;
    }
}
if (!function_exists('shorten_hash')) {
    function shorten_hash($hash, $prefix_length = 6, $suffix_length = 4) {
        return substr($hash, 0, $prefix_length) . '...' . substr($hash, -$suffix_length);
    }
}


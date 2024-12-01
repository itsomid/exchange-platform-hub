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

if (! function_exists('formatNumberWithSlashes')) {
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
//if (!function_exists('formatNumber')) {
//    function formatNumber($number, $decimals = 2)
//    {
//        return number_format($number, $decimals);
//    }
//}


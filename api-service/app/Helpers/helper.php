<?php

if (! function_exists('formatNumberTrimZeros')) {
    /**
     * Format a numeric string:
     *   - add thousands separators to integer part
     *   - trim trailing zeros in decimal part
     *   - optionally control decimal precision
     * e.g. "92789.75000400" => "92,789.750004"
     *
     * @param string|float|int $number   The number to format (passed as string recommended)
     * @param string           $thousand The thousands separator (default ",")
     * @param string           $decimal  The decimal separator (default ".")
     * @param int|null         $precision Optional decimal precision (if set, truncates to this many digits)
     */
    function formatNumberTrimZeros(string|float|int $number, ?int $precision = null, string $thousand = ',', string $decimal = '.'): string
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

            if ($precision !== null) {
                // Truncate to $precision digits and trim any new trailing zeros
                $decimalPart = substr($decimalPart, 0, $precision);
                $decimalPart = rtrim($decimalPart, '0');
            } elseif ($leadingZeros >= 4) {
                // Keep all digits (preserve significant digits after 4 zeros)
                // No truncation needed since trailing zeros are already trimmed
            } else {
                // Truncate to 4 digits and trim any new trailing zeros
                $decimalPart = substr($decimalPart, 0, 4);
                $decimalPart = rtrim($decimalPart, '0');
            }
        } else {
            if ($precision !== null) {
                $decimalPart = substr($decimalPart, 0, $precision);
                $decimalPart = rtrim($decimalPart, '0');
            } else {
                // For numbers >= 1, truncate to 2 digits and trim trailing zeros
                $decimalPart = substr($decimalPart, 0, 2);
                $decimalPart = rtrim($decimalPart, '0');
            }
        }

        // Format the integer part with thousands separators
        $integerPart = preg_replace('/\B(?=(\d{3})+(?!\d))/', $thousand, $integerPart);

        // If decimal part is empty, return only the integer part
        if ($decimalPart === '') {
            return $integerPart;
        }

        // Recombine parts with the decimal separator
        return $integerPart.$decimal.$decimalPart;
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
    function toDecimalString($number, $precision = 8): string
    {
        return sprintf('%.'.$precision.'f', (float) $number);
    }
}

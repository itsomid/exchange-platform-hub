<?php

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
     */
    function formatNumberTrimZeros(string|float|int $number, string $thousand = ',', string $decimal = '.'): string
    {
        // Convert to string to avoid float rounding issues
        $numberString = (string) $number;

        // Split into integer and decimal parts
        $parts = explode('.', $numberString);
        $integerPart = $parts[0];
        $decimalPart = $parts[1] ?? '';

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
        return $integerPart.$decimal.$decimalPart;
    }
}

<?php

namespace App\Utils;

use Throwable;

class RandomToken
{
    /**
     * Generates a random integer with a specified minimum and optional maximum length.
     *
     * This method generates a random integer within a range determined by the number of digits.
     * If only a minimum length is provided, it is used for both the minimum and maximum length.
     *
     * @param  int      $minLength The minimum length (number of digits) of the generated integer.
     * @param  int|null $maxLength The maximum length (number of digits) of the generated integer. Defaults to $minLength if not provided.
     * @return int      A random integer within the specified length range.
     */
    public static function generate(int $minLength, ?int $maxLength = null): int
    {
        // If no maximum length is provided, set it to the minimum length
        if (is_null($maxLength)) {
            $maxLength = $minLength;
        }
        try {
            // Generate a random integer within the specified digit length range
            return random_int(
                (int) ('1'.str_repeat('0', $minLength - 1)),
                (int) ('9'.str_repeat('9', $maxLength - 1))
            );
        } catch (Throwable $e) {
            report($e);

            return rand(
                (int) ('1'.str_repeat('0', $minLength - 1)),
                (int) ('9'.str_repeat('9', $maxLength - 1))
            );
        }
    }
}

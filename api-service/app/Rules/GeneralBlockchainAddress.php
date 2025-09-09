<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\ValidationException;

class GeneralBlockchainAddress implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! preg_match('/^[a-zA-Z0-9]{26,42}$/', $value)) {
            throw ValidationException::withMessages([
                $attribute => __('validation.blockchain_address', ['attribute' => __('validation.attributes.' . $attribute, [], app()->getLocale()) ?: $attribute])
            ]);
        }
    }
}

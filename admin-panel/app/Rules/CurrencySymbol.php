<?php

namespace App\Rules;

use App\Models\Currency;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CurrencySymbol implements ValidationRule
{
    protected $validCurrencies;

    public function __construct()
    {
        // Fetch all currency symbols from the 'currencies' table
        $this->validCurrencies = Currency::pluck('symbol')->toArray();
    }

    public function validate($attribute, $value, $fail): void
    {
        // Check if the value exists in the list of valid currencies
        if (!in_array($value, $this->validCurrencies)) {
            $fail('The ' . $attribute . ' must be a valid currency symbol.');
        }
    }
}

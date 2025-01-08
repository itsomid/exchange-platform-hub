<?php

namespace App\Rules;

use App\Models\Currency;
use App\Models\CurrencyChain;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CheckMinAmount implements ValidationRule
{
    public function __construct(private ?string $currency, private ?string $currencyChain) {}

    /**
     * Run the validation rule.
     *
     * @param \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $currency = Currency::query()
            ->where('symbol', $this->currency)
            ->first();
        $chain = CurrencyChain::query()
            ->where('chain', $this->currencyChain)
            ->where('currency_id', $currency->id)
            ->first();

        if (bccomp($value, $chain->min_withdraw_amount, config('bitexroom.scale_precision')) === -1) {
            $fail(__('validation.min_amount', [
                'min' => $chain->min_withdraw_amount,
                'currency' => $this->currency,
            ]));
        }
    }
}

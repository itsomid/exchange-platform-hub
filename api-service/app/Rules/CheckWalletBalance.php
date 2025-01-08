<?php

namespace App\Rules;

use App\Models\Wallet;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Auth;

class CheckWalletBalance implements ValidationRule
{
    public function __construct(private ?string $currency) {}

    /**
     * Run the validation rule.
     *
     * @param \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {

        $wallet = Wallet::query()
            ->where('user_id', Auth::id())
            ->where('currency_symbol', $this->currency)
            ->first();

        if (is_null($wallet) || bccomp($value, $wallet->balance, config('bitexroom.scale_precision')) === 1) {
            $fail(__('validation.insufficient_balance', [
                'amount' => $value,
                'currency' => $this->currency,
            ]));
        }
    }
}

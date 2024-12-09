<?php

namespace App\Rules;

use App\Models\NodeProvider;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UniquePriorityForCurrency implements ValidationRule
{
    protected $currencyId;

    public function __construct($currencyId)
    {
        $this->currencyId = $currencyId;
    }

    /**
     * Run the validation rule.
     *
     * @param \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, \Closure $fail): void
    {
        preg_match('/nodes\.(\d+)\.priority/', $attribute, $matches);
        $nodeId = $matches[1] ?? null;

        if (!$nodeId) {
            $fail("Invalid node ID provided.");
            return;
        }

        if (NodeProvider::where('currency_id', $this->currencyId)
            ->where('priority', $value)
            ->where('id', '!=', $nodeId)
            ->exists()) {
            $fail("اولویت شماره {$value} قبلا انتخاب شده است");
        }
    }
}

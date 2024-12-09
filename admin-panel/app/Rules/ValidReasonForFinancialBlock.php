<?php

namespace App\Rules;


use App\Enums\FinancialBlockReasonsEnum;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidReasonForFinancialBlock implements ValidationRule
{
    protected array $validReasons;
    public function __construct()
    {
        $this->validReasons = FinancialBlockReasonsEnum::cases();
    }
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!in_array($value, $this->validReasons, true)) {
            $fail("The {$attribute} must be a valid financial block reason.");
        }
    }
}

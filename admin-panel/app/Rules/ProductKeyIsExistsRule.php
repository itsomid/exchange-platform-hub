<?php

namespace App\Rules;

use App\Models\Product;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ProductKeyIsExistsRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_array($value)) {
            $fail("{$attribute} معتبر نمی باشد. ");

            return;
        }

        $productCount = Product::query()->whereIn('id', array_keys($value))->count();

        if ($productCount !== count($value)) {
            $fail("{$attribute} تعدادی از محصولات وجود ندارند. ");
        }
    }
}

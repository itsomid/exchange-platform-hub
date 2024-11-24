<?php

namespace App\Rules;

use App\Models\VerificationCode;
use Illuminate\Contracts\Validation\Rule;

class RequestValidRule implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct(private $mobile)
    {
        //
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        return VerificationCode::query()
            ->where('receptor', $this->mobile)
            ->where('code', $value)
            ->where('created_at', '>', now()->subMinutes(10))
            ->exists();
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'کد وارد شده صحیح نمیباشد لطفا مجددا وارد کنید.';
    }
}

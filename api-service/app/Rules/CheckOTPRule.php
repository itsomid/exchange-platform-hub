<?php

namespace App\Rules;

use App\Enums\EmailOTPActionEnum;
use App\Services\System\DTO\VerifyOTPRequestDTO;
use App\Services\System\EmailOTPService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Auth;

class CheckOTPRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $service = resolve(EmailOTPService::class);
        $isVerified = $service->verify(
            resolve(VerifyOTPRequestDTO::class)
                ->setEmail(Auth::user()->email)
                ->setCode($value)
                ->setAction(EmailOTPActionEnum::WITHDRAWAL)
        );

        if (! $isVerified) {
            $fail(__('validation.invalid_otp_code'));
        }

    }
}

<?php

namespace App\Rules;

use App\Exceptions\Auth\Google2faSecretInvalidException;
use App\Services\Auth\DTO\CheckTwoFactorRequestDTO;
use App\Services\Auth\TwoFactorService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Auth;
use Throwable;

class CheckTwoFactorRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (empty($value)) {
            return;
        }
        $twoFactorService = resolve(TwoFactorService::class);
        try {
            $twoFactorService->checkTwoFactor(
                resolve(CheckTwoFactorRequestDTO::class)
                    ->setUserId(Auth::id())
                    ->setGoogle2fa($value)
            );
        } catch (Google2faSecretInvalidException $exception) {
            $fail(__('validation.invalid_2fa_code'));
        } catch (Throwable $exception) {
            report($exception);
            $fail(__('validation.invalid_2fa_code'));
        }

    }
}

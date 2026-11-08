<?php

namespace App\Rules;

use App\Models\Admin;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Password policy for admins. The same requirement list drives the server-side
 * validation and the live checklist on the change-password page.
 */
class StrongPassword implements ValidationRule
{
    public const MIN_LENGTH = 12;

    public function __construct(private readonly ?Admin $admin = null) {}

    /**
     * Patterns are written to be valid in both PHP (PCRE) and JavaScript.
     *
     * @return array<int, array{key: string, label: string, pattern: string, flags: string, must_match: bool}>
     */
    public static function requirements(): array
    {
        return [
            ['key' => 'length', 'label' => 'حداقل '.self::MIN_LENGTH.' کاراکتر', 'pattern' => '^[\s\S]{'.self::MIN_LENGTH.',}$', 'flags' => '', 'must_match' => true],
            ['key' => 'lower', 'label' => 'حروف کوچک انگلیسی (a-z)', 'pattern' => '[a-z]', 'flags' => '', 'must_match' => true],
            ['key' => 'upper', 'label' => 'حروف بزرگ انگلیسی (A-Z)', 'pattern' => '[A-Z]', 'flags' => '', 'must_match' => true],
            ['key' => 'digit', 'label' => 'عدد (0-9)', 'pattern' => '[0-9]', 'flags' => '', 'must_match' => true],
            ['key' => 'special', 'label' => 'کاراکتر خاص (مثل ! @ # $ % ^ & *)', 'pattern' => '[^A-Za-z0-9\s]', 'flags' => '', 'must_match' => true],
            ['key' => 'weak', 'label' => 'بدون الگوهای ساده (مثل 1234، qwerty، password، admin)', 'pattern' => 'password|passwd|qwerty|admin|letmein|welcome|bitex|(\d)\1{3,}|0123|1234|2345|3456|4567|5678|6789|abcd', 'flags' => 'i', 'must_match' => false],
        ];
    }

    /** Lower-cased personal values (email name, mobile) the password must not contain. */
    public static function forbiddenTokens(?Admin $admin): array
    {
        if (! $admin) {
            return [];
        }

        $tokens = [
            strstr((string) $admin->email, '@', true) ?: '',
            (string) $admin->mobile,
        ];

        return array_values(array_filter(
            array_map('mb_strtolower', $tokens),
            fn (string $token) => mb_strlen($token) >= 4
        ));
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $value = (string) $value;

        foreach (self::requirements() as $requirement) {
            $matches = (bool) preg_match('~'.$requirement['pattern'].'~'.$requirement['flags'], $value);

            if ($matches !== $requirement['must_match']) {
                $fail('گذرواژه ضعیف است؛ الزام: '.$requirement['label']);
            }
        }

        foreach (self::forbiddenTokens($this->admin) as $token) {
            if (str_contains(mb_strtolower($value), $token)) {
                $fail('گذرواژه نباید شامل ایمیل یا شماره موبایل شما باشد.');
                break;
            }
        }
    }
}

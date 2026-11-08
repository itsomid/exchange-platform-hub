<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class AdminFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $gender = Arr::random(['male', 'female']);

        return [
            'mobile' => generatePhone(),
            'email' => fake()->unique()->safeEmail(),
            'first_name' => $gender == 'male' ? $this->getRandomMaleFirstName() : $this->getRandomFemaleFirstName(),
            'last_name' => $this->getRandomLastName(),
            'password' => Hash::make('11111111'),
            'gender' => $gender,
            // Admins must have 2FA active to use the panel, so the default factory admin does.
            'two_factor_secret' => encrypt('JBSWY3DPEHPK3PXP'),
            'two_factor_recovery_codes' => encrypt(json_encode(['recovery-code-1'])),
            'two_factor_confirmed_at' => now(),
            // Same for the password: a factory admin has just changed it.
            'password_changed_at' => now(),
        ];

    }

    /** Password older than the allowed age; the grace period starts on the first request. */
    public function withExpiredPassword(): static
    {
        return $this->state(fn () => [
            'password_changed_at' => now()->subMonths(6)->subDay(),
        ]);
    }

    /** Never changed the password (e.g. created by another admin). */
    public function withoutPasswordChange(): static
    {
        return $this->state(fn () => ['password_changed_at' => null]);
    }

    public function withoutTwoFactor(): static
    {
        return $this->state(fn () => [
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ]);
    }

    private function getRandomMaleFirstName(): string
    {
        $maleNames = ['رضا', 'سجاد', 'شایان', 'دانیال', 'افشین', 'فربد', 'مصطفی', 'مهرداد', 'حامد', 'سپهر', 'محمد', 'علی', 'محمد رضا', 'حسین', 'فرید', 'امیر', 'علیرضا'];

        return Arr::random($maleNames);
    }

    private function getRandomFemaleFirstName(): string
    {
        $femaleNames = ['یاسمین', 'مینا', 'درسا', 'فاطمه', 'مهدیه', 'الهه', 'سارا', 'نگار', 'نگین', 'راحله', 'سمانه', 'شیما', 'مهسا', 'هدیه', 'هلما', 'حمیرا'];

        return Arr::random($femaleNames);
    }

    private function getRandomLastName(): string
    {
        $lastNames = ['رضاپور', 'ابراهیمی', 'مرادی', 'میبدی', 'طاهری', 'موسوی', 'پناهی', 'آذری', 'قاضیان', 'شمسی', 'فلاح', 'محمدی', 'ترکاشوند', 'فتحیان', 'تبریزی', 'خراسانی', 'گودرزی', 'شریفی', 'شهبازی', 'حاتمی', 'نعمتی', 'کاظم زاده', 'علیپور', 'رضایی', 'کریمی', 'رحمانی', 'تاجیک', 'حیدری', 'خسروی', 'جهانی'];

        return Arr::random($lastNames);
    }
}

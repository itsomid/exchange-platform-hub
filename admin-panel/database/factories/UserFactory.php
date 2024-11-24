<?php

namespace Database\Factories;

use App\Models\Admin;
use App\Models\ReferralCode;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $gender = Arr::random(['male', 'female', null]);

        return [
            'first_name' => ($gender == 'male' ? $this->getRandomMaleFirstName() : $this->getRandomFemaleFirstName()),
            'last_name' => $this->getRandomLastName(),
            'username' => fake()->unique()->userName(),
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('password'),
            'mobile' => $this->generatePhone(),
            'balance' => 0,
        ];

    }

    public function withReferralCode()
    {
        return $this->state(function (array $attributes) {
            return [
                'introducer_code' => ReferralCode::inRandomOrder()->value('id'), // Creates a referral code and assigns it
            ];
        });
    }

    public function unverified(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'email_verified_at' => null,
            ];
        });
    }

    public function withCustomRole(array $states, $roleName): UserFactory|Factory
    {
        return $this->state($states)->afterCreating(function (Admin $admin) use ($roleName) {
            $role = Role::findByName($roleName);
            $admin->assignRole($role);
        });
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

    function generatePhone(): string
    {
        return '09'.Arr::random(['02', '10', '38', '35', '90', '22', '12', '15', '19']).rand(1000000, 9999999);
    }
}

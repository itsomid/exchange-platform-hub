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
        ];

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

<?php

namespace App\Models;

use Faker\Factory as Faker;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReferralCode extends Model
{
    use HasFactory, SoftDeletes;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public static function generateReferralCode(): string
    {
        $faker = Faker::create();
        $prefix = 'REF';

        // Generate a 3-digit number, padded with zeros
        $numbers = str_pad((string) $faker->unique()->numberBetween(10, 99), 2, '0', STR_PAD_LEFT);

        // Generate 3 random uppercase letters
        $letters = strtoupper($faker->unique()->lexify('???'));

        // Generate another 3-digit number, padded with zeros
        $suffix = str_pad((string)$faker->unique()->numberBetween(100, 999), 3, '0', STR_PAD_LEFT);

        // Combine all parts to form the code
        return $prefix.$numbers.$letters.$suffix;
    }
}

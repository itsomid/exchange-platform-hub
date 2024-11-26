<?php

namespace App\Models;

use Carbon\Carbon;
use Faker\Factory as Faker;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int    $id
 * @property Carbon $created_at
 * @property int    $introducer_fee
 * @property int    $friend_fee
 * @property int    $transactions_sum_amount
 * @property int    $referral_code_usage_count
 * @property int    $registered_users_count
 * @property string $code
 */
class ReferralCode extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'friend_fee',
        'introducer_fee',
        'usage_limit',
        'user_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function registeredUsers(): HasMany
    {
        return $this->hasMany(User::class, 'introducer_code');
    }

    public function referralCodeUsage(): HasMany
    {
        return $this->hasMany(ReferralCodeUsage::class, 'referral_code_id');
    }

    public function transactions(): HasManyThrough
    {
        return $this->hasManyThrough(Transaction::class, ReferralCodeUsage::class, 'referral_code_id', 'id', 'id', 'transaction_id');
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
        $suffix = str_pad((string) $faker->unique()->numberBetween(100, 999), 3, '0', STR_PAD_LEFT);

        // Combine all parts to form the code
        return $prefix.$numbers.$letters.$suffix;
    }
}

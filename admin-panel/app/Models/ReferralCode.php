<?php

namespace App\Models;

use App\Enums\TransactionSubTypeEnum;
use App\Filters\Filterable;
use Faker\Factory as Faker;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReferralCode extends Model
{
    use Filterable, HasFactory, SoftDeletes;

    public $filterNameSpace = 'App\Filters\ReferralCodeFilters';

    public $fillable = [
        'code',
        'user_id',
        'introducer_fee',
        'friend_fee',
        'usage_limit'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function registeredUsers(): HasMany
    {
        return $this->hasMany(User::class,'introducer_code');
    }

    public function referralCodeUsage(): HasMany
    {
        return $this->hasMany(ReferralCodeUsage::class,'referral_code_id');
    }
    public function friendsReferralCodeUsage(): HasMany
    {

        return $this->hasMany(ReferralCodeUsage::class, 'referral_code_id')->where('type','friend');
    }

    public function introducerReferralCodeUsage(): HasMany
    {
        return $this->hasMany(ReferralCodeUsage::class, 'referral_code_id')->where('type','introducer');
    }
    public function transactions(): HasManyThrough
    {
        return $this->hasManyThrough(Transaction::class,ReferralCodeUsage::class,'referral_code_id','id','id','transaction_id');
    }

    public function introducerTransactions(): HasManyThrough
    {
        return $this->hasManyThrough(
            Transaction::class,
            ReferralCodeUsage::class,
            'referral_code_id', // Foreign key on ReferralCodeUsage table
            'id', // Foreign key on Transaction table
            'id', // Local key on ReferralCode table
            'transaction_id' // Local key on ReferralCodeUsage table
        )->where('transactions.subtype', TransactionSubTypeEnum::REFERRAL_INTRODUCER);
    }

    public function friendsTransactions(): HasManyThrough
    {
        return $this->hasManyThrough(
            Transaction::class,
            ReferralCodeUsage::class,
            'referral_code_id', // Foreign key on ReferralCodeUsage table
            'id', // Foreign key on Transaction table
            'id', // Local key on ReferralCode table
            'transaction_id' // Local key on ReferralCodeUsage table
        )->where('transactions.subtype', TransactionSubtypeEnum::REFERRAL_FRIEND);
    }

    public static function generateReferralCode()
    {
        $faker = Faker::create();
        $prefix = 'REF';

        // Generate a 3-digit number, padded with zeros
        $numbers = str_pad($faker->unique()->numberBetween(10, 99), 2, '0', STR_PAD_LEFT);

        // Generate 3 random uppercase letters
        $letters = strtoupper($faker->unique()->lexify('???'));

        // Generate another 3-digit number, padded with zeros
        $suffix = str_pad($faker->unique()->numberBetween(100, 999), 3, '0', STR_PAD_LEFT);

        // Combine all parts to form the code
        return $prefix.$numbers.$letters.$suffix;
    }
}

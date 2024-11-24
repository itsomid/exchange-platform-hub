<?php

namespace App\Models;

use App\Filters\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Laravel\Sanctum\HasApiTokens;

class Transaction extends Model
{
    use Filterable, HasApiTokens, HasFactory;

    public function referralCodeUsage(): HasOne
    {
        return $this->hasOne(ReferralCodeUsage::class, 'transaction_id');
    }
}

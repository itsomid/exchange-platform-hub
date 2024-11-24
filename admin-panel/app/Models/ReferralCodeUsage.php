<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ReferralCodeUsage extends Model
{
    public function referralCode(): BelongsTo
    {
        return $this->belongsTo(ReferralCode::class,'referral_code_id');
    }
    public function transaction() : BelongsTo
    {
        return $this->belongsTo(Transaction::class,'transaction_id');
    }
}

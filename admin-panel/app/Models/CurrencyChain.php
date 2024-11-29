<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CurrencyChain extends Model
{
    public function currency() : BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }
    public function calculateTotalWithdrawalFee()
    {
        return $this->network_fee + $this->exchange_fee;
    }
}

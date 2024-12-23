<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CurrencyChain extends Model
{
    protected $fillable = [
        'currency_id',
        'chain',
        'min_deposit_amount',
        'min_withdraw_amount',
        'deposit_delay_minutes',
        'safe_confirmations',
        'exchange_withdrawal_fee',
        'network_fee',
        'deposit_enabled',
        'withdraw_enabled'
    ];

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function calculateTotalWithdrawalFee()
    {
        return bcadd($this->network_fee, $this->exchange_withdrawal_fee, 8);
    }
}

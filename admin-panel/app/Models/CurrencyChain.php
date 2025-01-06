<?php

namespace App\Models;

use App\Enums\CurrencyBlockChainNameEnum;
use App\Enums\CurrencyChainEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CurrencyChain extends Model
{
    protected $fillable = [
        'currency_id',
        'chain',
        'blockchain_name',
        'min_deposit_amount',
        'min_withdraw_amount',
        'deposit_delay_minutes',
        'safe_confirmations',
        'exchange_withdrawal_fee',
        'network_fee',
        'deposit_enabled',
        'withdraw_enabled',
    ];

    protected $casts = [
        'network_fee' => 'float',
        'exchange_withdrawal_fee' => 'float',
        'chain' => CurrencyChainEnum::class,
        'blockchain_name' => CurrencyBlockChainNameEnum::class,
    ];

    protected $appends = ['total_withdrawal_fee'];

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function getTotalWithdrawalFeeAttribute()
    {
        return bcadd($this->network_fee, $this->exchange_withdrawal_fee, 8);
    }

    /**
     * Scope to get total withdrawal fee using a database query
     */
    public function scopeTotalWithdrawalFee($query, $currencyChain)
    {
        return $query->where('chain', $currencyChain)
            ->sum(\DB::raw('network_fee + exchange_withdrawal_fee'));
    }
}

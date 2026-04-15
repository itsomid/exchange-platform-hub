<?php

namespace App\Models;

use App\Enums\CurrencyBlockChainNameEnum;
use App\Enums\CurrencyChainEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class CurrencyChain extends Model
{
    protected $fillable = [
        'currency_id',
        'chain',
        'chain_name',
        'blockchain_name',
        'min_deposit_amount',
        'min_withdraw_amount',
        'deposit_delay_minutes',
        'safe_confirmations',
        'exchange_withdrawal_fee',
        'network_fee',
        'deposit_enabled',
        'withdraw_enabled',
        'withdrawal_precision',
        'memo',
        'is_memo_required_for_deposit',
        'explorer_address_url',
        'explorer_tx_url',
        'contract_address',
        'is_base_coin',
    ];

    protected $casts = [
        'network_fee' => 'decimal:8',
        'exchange_withdrawal_fee' => 'decimal:8',
        'min_deposit_amount' => 'decimal:8',
        'min_withdraw_amount' => 'decimal:8',
        'deposit_enabled' => 'boolean',
        'withdraw_enabled' => 'boolean',
        'is_memo_required_for_deposit' => 'boolean',
        'is_base_coin' => 'boolean',
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
        return $this->network_fee + $this->exchange_withdrawal_fee;
    }

    /**
     * Scope to get total withdrawal fee using a database query
     */
    public function scopeTotalWithdrawalFee($query, $currencyChain)
    {
        return $query->where('chain', $currencyChain)
            ->sum(DB::raw('network_fee + exchange_withdrawal_fee'));
    }
}

<?php

namespace App\Models;

use App\Enums\CurrencyBlockChainNameEnum;
use App\Enums\CurrencyChainEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

/**
 * @property CurrencyBlockChainNameEnum $blockchain_name
 *
 * @method string totalWithdrawalFee(string $getCurrencyChain)
 */
class CurrencyChain extends Model
{
    protected $casts = [
        'network_fee' => 'float',
        'exchange_withdrawal_fee' => 'float',
        'chain' => CurrencyChainEnum::class,
        'blockchain_name' => CurrencyBlockChainNameEnum::class,
    ];

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function scopeTotalWithdrawalFee($query, $currencyChain)
    {
        return $query->where('chain', $currencyChain)
            ->sum(DB::raw('network_fee + exchange_withdrawal_fee'));
    }
}

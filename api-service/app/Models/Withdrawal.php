<?php

namespace App\Models;

use App\Enums\WithdrawalStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string               $amount
 * @property string               $currency_symbol
 * @property string               $currency_chain
 * @property WithdrawalStatusEnum $status
 * @property int                  $user_id
 */
class Withdrawal extends Model
{
    protected $fillable = [
        'user_id',
        'admin_id',
        'wallet_id',
        'currency_chain',
        'currency_symbol',
        'amount',
        'network_fee',
        'exchange_fee',
        'total_fee',
        'address',
        'transaction_hash',
        'status',
        'description',
        'confirmed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => WithdrawalStatusEnum::class,
            'confirmed_at' => 'datetime',
        ];
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_symbol', 'symbol');
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'currency_symbol', 'currency_symbol');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

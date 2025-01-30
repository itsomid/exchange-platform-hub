<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExchangeAssetsWithdrawal extends Model
{
    protected $fillable = [
        'admin_id',
        'withdrawal_id',
        'currency_fee',
        'fee',
        'currency_symbol',
        'currency_chain',
        'amount',
        'actual_amount',
        'hd_wallet_address',
        'withdrawal_date',
        'explore_address_url',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'withdrawal_date' => 'datetime',
        ];
    }
}

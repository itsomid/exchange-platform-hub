<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExchangeAssetsWithdrawal extends Model
{
    protected $fillable = [
        'admin_id',
        'exchange',
        'withdrawal_id',
        'fee_currency',
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

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_symbol','symbol');
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'admin_id','id');
    }
}

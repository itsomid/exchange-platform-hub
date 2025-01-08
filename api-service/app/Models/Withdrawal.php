<?php

namespace App\Models;

use App\Enums\WithdrawalStatusEnum;
use Illuminate\Database\Eloquent\Model;

class Withdrawal extends Model
{
    protected $fillable = [
        'user_id', 'admin_id', 'wallet_id', 'currency_chain', 'currency_symbol', 'amount', 'fee', 'address', 'transaction_hash', 'status', 'description', 'confirmed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => WithdrawalStatusEnum::class,
            'confirmed_at' => 'datetime',
        ];
    }
}

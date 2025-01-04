<?php

namespace App\Models;

use App\Enums\DepositStatusEnum;
use Illuminate\Database\Eloquent\Model;

class Deposit extends Model
{
    protected $fillable = [
        'user_id', 'currency_chain', 'currency_symbol', 'amount', 'address', 'status', 'expiration_date', 'confirmed_at', 'transaction_hash',
    ];

    protected function casts(): array
    {
        return [
            'status' => DepositStatusEnum::class,
            'expiration_date' => 'datetime',
            'confirmed_at' => 'datetime',
        ];
    }
}

<?php

namespace App\Models;

use App\Enums\DepositStatusEnum;
use Illuminate\Database\Eloquent\Model;

class Deposit extends Model
{
    protected $fillable = [
        'user_id', 'currency_chain', 'currency_symbol', 'amount', 'public_key', 'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => DepositStatusEnum::class,
        ];
    }
}

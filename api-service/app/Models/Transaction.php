<?php

namespace App\Models;

use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionSubTypeEnum;
use App\Enums\TransactionTypeEnum;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'user_id', 'admin_id', 'wallet_id', 'amount', 'balance', 'type', 'subtype', 'description', 'admin_description', 'status',
    ];

    protected function casts(): array
    {
        return [
            'type' => TransactionTypeEnum::class,
            'subtype' => TransactionSubTypeEnum::class,
            'status' => TransactionStatusEnum::class,
        ];
    }
}

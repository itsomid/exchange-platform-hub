<?php

namespace App\Models;

use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionSubTypeEnum;
use App\Enums\TransactionTypeEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property string                $amount
 * @property TransactionStatusEnum $status
 * @property TransactionTypeEnum   $type
 */
class Transaction extends Model
{
    protected $fillable = [
        'user_id', 'admin_id', 'wallet_id', 'deposit_id', 'withdrawal_id', 'otc_order_id', 'amount', 'balance', 'type', 'subtype', 'description', 'admin_description', 'status',
    ];

    protected function casts(): array
    {
        return [
            'type' => TransactionTypeEnum::class,
            'subtype' => TransactionSubTypeEnum::class,
            'status' => TransactionStatusEnum::class,
        ];
    }

    public function deposit(): HasOne
    {
        return $this->hasOne(Deposit::class, 'id', 'deposit_id');
    }

    public function withdrawal(): HasOne
    {
        return $this->hasOne(Withdrawal::class, 'id', 'withdrawal_id');
    }
}

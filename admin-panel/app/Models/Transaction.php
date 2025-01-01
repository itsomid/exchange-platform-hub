<?php

namespace App\Models;

use App\Enums\TransactionSubTypeEnum;
use App\Enums\TransactionTypeEnum;
use App\Filters\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Laravel\Sanctum\HasApiTokens;

class Transaction extends Model
{
    use Filterable, HasApiTokens, HasFactory;

    public $filterNameSpace = 'App\Filters\TransactionFilter';

    protected $fillable = [
        'user_id', 'admin_id', 'wallet_id', 'amount', 'balance', 'type', 'subtype', 'description', 'admin_description', 'status', 'total', 'fee'
    ];

    protected function casts(): array
    {
        return [
            'type' => TransactionTypeEnum::class,
            'subtype' => TransactionSubTypeEnum::class
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    public function referralCodeUsage(): HasOne
    {
        return $this->hasOne(ReferralCodeUsage::class, 'transaction_id');
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function OTCOrder()
    {
        return $this->belongsTo(OTCOrder::class,'otc_order_id');
    }

    public function deposit()
    {
        return $this->belongsTo(Deposit::class,'deposit_id');
    }
}

<?php

namespace App\Models;

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
        'amount', 'user_id', 'type', 'description','status'
    ];
    protected function casts(): array
    {
        return [
            'type' => TransactionTypeEnum::class
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    public function referralCodeUsage(): HasOne
    {
        return $this->hasOne(ReferralCodeUsage::class, 'transaction_id');
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }
}

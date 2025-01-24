<?php

namespace App\Models;

use App\Enums\TransactionTypeEnum;
use App\Enums\WithdrawalStatusEnum;
use App\Filters\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Withdrawal extends Model
{
    use Filterable, HasApiTokens, Notifiable;

    public $filterNameSpace = 'App\Filters\WithdrawalFilter';

    protected $fillable = [
        'user_id', 'admin_id', 'wallet_id', 'currency_chain', 'currency_symbol', 'amount', 'total_fee', 'address', 'transaction_hash', 'status', 'description', 'confirmed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => WithdrawalStatusEnum::class,
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_symbol', 'symbol');
    }
    public function currencyChainName()
    {
        return $this->hasOneThrough(
            CurrencyChain::class,
            Currency::class,
            'symbol', // Foreign key on Currency table
            'currency_id', // Foreign key on CurrencyChain table
            'currency_symbol', // Local key on Deposit table
            'id' // Local key on Currency table
        );
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'withdrawal_id');
    }
    public function transaction()
    {
        return $this->hasOne(Transaction::class, 'withdrawal_id');
    }

    public function feeTransaction()
    {
        return $this->hasOne(Transaction::class, 'withdrawal_id')->where('type', TransactionTypeEnum::FEE);
    }

    public function wallet()
    {
        return $this->hasOne(Wallet::class, 'user_id', 'user_id')
            ->where(function ($query) {
                $query->where('currency_symbol', $this->currency_symbol);
            });
    }

}

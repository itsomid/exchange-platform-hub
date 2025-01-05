<?php

namespace App\Models;

use App\Enums\DepositStatusEnum;
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
        'user_id','admin_id','wallet_id', 'currency_chain', 'currency_symbol', 'amount','fee', 'address','transaction_hash', 'status','description', 'confirmed_at',
    ];
    protected $casts = [
        'status' => WithdrawalStatusEnum::class
    ];
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
        return $this->belongsTo(Currency::class,'currency_symbol','symbol');
    }

    public function transaction()
    {
        return $this->hasOne(Transaction::class,'withdrawal_id');
    }

    public function wallet()
    {
        return $this->hasOne(Wallet::class, 'user_id', 'user_id')
            ->where(function ($query) {
                $query->where('currency_symbol', $this->currency_symbol);
            });
    }
}

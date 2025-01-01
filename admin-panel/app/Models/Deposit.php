<?php

namespace App\Models;

use App\Enums\DepositStatusEnum;
use Illuminate\Database\Eloquent\Model;

class Deposit extends Model
{

    protected $fillable = [
        'user_id', 'currency_chain', 'currency_symbol', 'amount', 'address', 'status', 'expiration_date',
    ];
    protected $casts = [
      'status' => DepositStatusEnum::class
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function currency()
    {
        return $this->belongsTo(Currency::class,'currency_symbol','symbol');
    }

    public function transaction()
    {
        return $this->hasOne(Transaction::class,'deposit_id');
    }

}

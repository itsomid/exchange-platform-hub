<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SavedAddress extends Model
{
    protected $fillable = ['user_id', 'name', 'address', 'currency'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class,'currency_symbol');
    }

    public function currencyChain()
    {
        return $this->belongsTo(Currency::class,'currency_chain');
    }
}

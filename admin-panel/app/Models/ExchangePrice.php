<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExchangePrice extends Model
{
    protected $fillable = ['market_id', 'exchange_id', 'price', 'open_price', 'exchange_profit_sell', 'exchange_profit_buy'];

    public function exchange()
    {
        return $this->belongsTo(Exchange::class);
    }

    public function market()
    {
        return $this->belongsTo(Market::class);
    }

    public function getPriceChangePercentageAttribute(): ?float
    {
        if ($this->open_price > 0) {
            return (($this->price - $this->open_price) / $this->open_price) * 100;
        }
        return null;


    }

    public function getExchangeSellPriceAttribute()
    {
        return ($this->price * ($this->exchange_profit_sell / 100)) + $this->price;
    }

    public function getExchangeBuyPriceAttribute()
    {
        return ($this->price * ( $this->exchange_profit_buy / 100 )) + $this->price;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property string     $max_auto_withdraw_amount
 * @property Collection $chains
 * @property int        $precision
 * @property string     $symbol
 * @property string     $name
 * @property bool       $inter_transfer_enabled
 */
class Currency extends Model
{
    protected $fillable = [

    ];

    public function baseMarket(): HasOne
    {
        return $this->hasOne(Market::class, 'base_currency', 'symbol');
    }

    public function getExchangePriceAttribute()
    {
        return $this->baseMarket && $this->baseMarket->activeExchangePrice
            ? $this->baseMarket->activeExchangePrice->price
            : 1; // Default to 1 if no exchange rate is found
    }

    public function chains(): HasMany
    {
        return $this->hasMany(CurrencyChain::class);
    }
}

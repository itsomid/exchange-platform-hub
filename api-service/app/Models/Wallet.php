<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

/**
 * @property int           $id
 * @property string        $balance
 * @property string        $currency_symbol
 * @property string        $locked_balance
 * @property ExchangePrice $exchangePrice
 * @property Market        $market
 */
class Wallet extends Model
{
    protected $fillable = [
        'user_id', 'currency_symbol', 'balance', 'locked_balance',
    ];

    public function exchangePrice(): HasOneThrough
    {
        return $this->hasOneThrough(ExchangePrice::class, Market::class, 'base_currency', 'market_id', 'currency_symbol', 'id');
    }

    public function market(): BelongsTo
    {
        return $this->belongsTo(Market::class, 'currency_symbol', 'base_currency');
    }
}

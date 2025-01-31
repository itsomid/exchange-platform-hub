<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

/**
 * @property int           $id
 * @property string        $balance
 * @property string        $locked_balance
 * @property string        $available
 * @property string        $currency_symbol
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

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_symbol', 'symbol');
    }

    public function getAvailableAttribute(): string
    {
        return bcsub($this->balance, $this->locked_balance, 8);
    }

    public function chains(): HasMany
    {
        return $this->hasMany(WalletChain::class);
    }
}

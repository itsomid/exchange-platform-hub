<?php

namespace App\Models;

use App\Filters\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Currency extends Model
{
    use filterable, HasFactory;

    public $filterNameSpace = 'App\Filters\CurrencyFilter';

    protected $fillable = [
        'name',
        'symbol',
        'logo',
        'inter_transfer_enabled',
        'max_auto_withdraw_amount'
    ];

    public function chains() : HasMany
    {
        return $this->hasMany(CurrencyChain::class,'currency_id');
    }
    public function baseMarket(): HasOne
    {
        return $this->hasOne(Market::class, 'base_currency', 'symbol');
    }
// In your Currency model
    public function getExchangePriceAttribute()
    {
        return $this->baseMarket && $this->baseMarket->activeExchangePrice
            ? $this->baseMarket->activeExchangePrice->price
            : 1; // Default to 1 if no exchange rate is found
    }
    // Relationship: A currency can have many markets where it is the quote currency
    public function quoteMarket(): HasOne
    {
        return $this->hasOne(Market::class, 'quote_currency', 'symbol');
    }

    public function NodeProviders() : HasMany
    {
        return $this->hasMany(NodeProvider::class,'currency_id');
    }

    public function wallets()
    {
        return $this->hasMany(Wallet::class,'currency_symbol');
    }
    public function coinLogo(): string
    {
        $logoPath = storage_path("app/public/coins/{$this->logo}");
        if (file_exists($logoPath)) {
            return asset("storage/coins/{$this->logo}");
        }

        return asset("images/coins/{$this->logo}");
    }

    public function interTransferStatus()
    {
        return (bool)$this->inter_transfer_enabled;
    }

}

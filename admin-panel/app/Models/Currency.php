<?php

namespace App\Models;

use App\Filters\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Currency extends Model
{
    use filterable, HasFactory;

    public $filterNameSpace = 'App\Filters\CurrencyFilter';

    protected $fillable = [
        'name',
        'symbol',
        'logo',
        'inter_transfer_enabled'
    ];

    public function chains() : HasMany
    {
        return $this->hasMany(CurrencyChain::class,'currency_id');
    }
    public function baseMarkets(): HasMany
    {
        return $this->hasMany(Market::class, 'base_currency', 'symbol');
    }

    // Relationship: A currency can have many markets where it is the quote currency
    public function quoteMarkets(): HasMany
    {
        return $this->hasMany(Market::class, 'quote_currency', 'symbol');
    }

    public function NodeProviders() : HasMany
    {
        return $this->hasMany(NodeProvider::class,'currency_id');
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

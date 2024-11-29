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

    public $fillable = ['type','name','symbol','code','logo','status'];

    public function chains() : HasMany
    {
        return $this->hasMany(CurrencyChain::class,'currency_id');
    }
    public function coinLogo(): string
    {
        return asset("images/coins/{$this->logo}");
    }

    public function interTransferStatus()
    {
        return (bool)$this->inter_transfer_enabled;
    }

}

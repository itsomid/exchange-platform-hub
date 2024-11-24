<?php

namespace App\Models;

use App\Filters\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    use filterable, HasFactory;

    public $filterNameSpace = 'App\Filters\CurrencyFilter';

    public $fillable = ['type','name','symbol','code','logo','status'];
    public function getTypeAttribute($value)
    {
        return strtoupper($value); // Example: 'erc20' becomes 'Erc20'
    }

    public function coinLogo(): string
    {
        return asset("images/coins/{$this->logo}");
    }
    public function status()
    {
        return (bool)$this->is_active;
    }
}

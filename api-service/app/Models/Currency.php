<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Currency extends Model
{
    protected $fillable = [

    ];
    public function chains(): HasMany
    {
        return $this->hasMany(CurrencyChain::class);
    }
}

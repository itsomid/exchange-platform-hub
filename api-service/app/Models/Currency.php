<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string     $max_auto_withdraw_amount
 * @property Collection $chains
 */
class Currency extends Model
{
    protected $fillable = [

    ];

    public function chains(): HasMany
    {
        return $this->hasMany(CurrencyChain::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $address
 * @property int    $id
 */
class WalletChain extends Model
{
    protected $fillable = [
        'wallet_id', 'currency_chain', 'address',
    ];

    public function currencyChain(): BelongsTo
    {
        return $this->belongsTo(CurrencyChain::class, 'currency_chain', 'chain');
    }
}

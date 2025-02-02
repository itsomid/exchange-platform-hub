<?php

namespace App\Models;

use App\Enums\CurrencyChainEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string        $address
 * @property int           $id
 * @property CurrencyChain $currencyChain
 */
class WalletChain extends Model
{
    protected $fillable = [
        'wallet_id', 'currency_chain', 'address',
    ];

    protected function casts(): array
    {
        return [
            'currency_chain' => CurrencyChainEnum::class,
        ];
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }
}

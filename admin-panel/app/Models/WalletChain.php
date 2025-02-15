<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletChain extends Model
{

    protected $fillable = ['wallet_id','currency_chain','address'];
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function currencyChain()
    {
        return $this->belongsTo(CurrencyChain::class,'currency_chain','chain');
    }

    public function getExplorerAddressUrlAttribute()
    {

        if (!$this->address || !$this->currencyChain?->explorer_address_url) {
            return $this->currencyChain;
        }
//        return $this->currencyChain;
        return str_replace('{address}', $this->address, $this->currencyChain->explorer_address_url);
    }

}

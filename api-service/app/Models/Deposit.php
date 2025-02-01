<?php

namespace App\Models;

use App\Enums\DepositStatusEnum;
use Illuminate\Database\Eloquent\Model;

class Deposit extends Model
{
    protected $fillable = [
        'user_id', 'currency_chain', 'currency_symbol', 'amount', 'address', 'status', 'expiration_date', 'confirmed_at', 'transaction_hash',
    ];
    protected $appends = ['explorer_address_url' , 'explorer_tx_url'];
    protected function casts(): array
    {
        return [
            'status' => DepositStatusEnum::class,
            'expiration_date' => 'datetime',
            'confirmed_at' => 'datetime',
        ];
    }
    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_symbol', 'symbol');
    }

    public function currencyChain()
    {
        return $this->hasOneThrough(
            CurrencyChain::class,
            Currency::class,
            'symbol', // Foreign key on Currency table
            'currency_id', // Foreign key on CurrencyChain table
            'currency_symbol', // Local key on Deposit table
            'id' // Local key on Currency table
        );
    }

    public function getExplorerAddressUrlAttribute()
    {

        if (!$this->address || !$this->currencyChain?->explorer_address_url) {
            return null;
        }

        return str_replace('{address}', $this->address, $this->currencyChain->explorer_address_url);
    }

    public function getExplorerTxUrlAttribute()
    {
        if (!$this->transaction_hash || !$this->currencyChain?->explorer_tx_url) {
            return null;
        }

        return str_replace('{hash}', $this->transaction_hash, $this->currencyChain->explorer_tx_url);
    }
}

<?php

namespace App\Models;

use App\Enums\DepositStatusEnum;
use App\Enums\DepositTypeEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Deposit extends Model
{
    protected $fillable = [
        'user_id',
        'currency_chain_id',
        'currency_symbol',
        'amount',
        'address',
        'status',
        'type',
        'expiration_date',
        'confirmed_at',
        'transaction_hash',
        'usdt_value',
    ];

    protected $appends = ['explorer_address_url', 'explorer_tx_url'];

    protected function casts(): array
    {
        return [
            'status' => DepositStatusEnum::class,
            'type' => DepositTypeEnum::class,
            'expiration_date' => 'datetime',
            'confirmed_at' => 'datetime',
        ];
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_symbol', 'symbol');
    }

    public function currencyChain(): BelongsTo
    {
        return $this->belongsTo(CurrencyChain::class);
    }

    public function getExplorerAddressUrlAttribute()
    {

        if (! $this->address || ! $this->currencyChain?->explorer_address_url) {
            return null;
        }

        return str_replace('{address}', $this->address, $this->currencyChain->explorer_address_url);
    }

    public function getExplorerTxUrlAttribute()
    {
        if (! $this->transaction_hash || ! $this->currencyChain?->explorer_tx_url) {
            return null;
        }

        return str_replace('{hash}', $this->transaction_hash, $this->currencyChain->explorer_tx_url);
    }
}

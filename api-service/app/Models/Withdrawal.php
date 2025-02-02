<?php

namespace App\Models;

use App\Enums\WithdrawalStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string               $amount
 * @property string               $currency_symbol
 * @property string               $currency_chain
 * @property WithdrawalStatusEnum $status
 * @property int                  $user_id
 */
class Withdrawal extends Model
{
    protected $fillable = [
        'user_id',
        'admin_id',
        'wallet_id',
        'currency_chain',
        'currency_symbol',
        'amount',
        'usdt_value',
        'network_fee',
        'exchange_fee',
        'hd_wallet_network_fee',
        'total_fee',
        'address',
        'transaction_hash',
        'status',
        'description',
        'confirmed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => WithdrawalStatusEnum::class,
            'confirmed_at' => 'datetime',
        ];
    }

    protected $appends = ['explorer_address_url', 'explorer_tx_url'];

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

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'currency_symbol', 'currency_symbol');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

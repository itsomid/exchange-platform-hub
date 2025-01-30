<?php

namespace App\Models;

use App\Enums\DepositStatusEnum;
use App\Filters\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;


class Deposit extends Model
{
    use Filterable, HasApiTokens, Notifiable;

    public $filterNameSpace = 'App\Filters\DepositFilter';

    protected $fillable = [
        'user_id', 'currency_chain', 'currency_symbol', 'amount', 'address', 'status', 'description', 'expiration_date',
    ];
    protected $casts = [
        'status' => DepositStatusEnum::class
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
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

    public function transaction()
    {
        return $this->hasOne(Transaction::class, 'deposit_id');
    }

    public function wallet()
    {
        return $this->hasOne(Wallet::class, 'user_id', 'user_id')
            ->where(function ($query) {
                $query->where('currency_symbol', $this->currency_symbol);
            });
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

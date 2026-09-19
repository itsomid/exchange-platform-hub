<?php

namespace App\Models;

use App\Enums\DepositStatusEnum;
use App\Enums\DepositTypeEnum;
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
        'user_id', 'currency_chain_id', 'currency_symbol', 'amount','usdt_value', 'address','transaction_hash','confirmed_at', 'status', 'type', 'description', 'expiration_date',
    ];
    protected $casts = [
        'status' => DepositStatusEnum::class,
        'type' => DepositTypeEnum::class,
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
        return $this->belongsTo(CurrencyChain::class,'currency_chain_id');
    }

    public function transaction()
    {
        return $this->hasOne(Transaction::class, 'deposit_id');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'deposit_id');
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

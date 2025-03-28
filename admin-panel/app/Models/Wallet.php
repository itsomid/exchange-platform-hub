<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wallet extends Model
{
    use HasFactory;
    protected $fillable = ['user_id','currency_symbol','balance','locked_balance','description'];
    public function user() : BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_symbol', 'symbol');
    }
    public function currencyChain()
    {
        return $this->belongsTo(CurrencyChain::class, 'currency_chain');
    }
    public function walletChains() : HasMany
    {
        return $this->hasMany(WalletChain::class);
    }
    public function coinLogo()
    {
        $logoPath = storage_path("app/public/coins/{$this->currency_symbol}");
        if (file_exists($logoPath)) {
            return asset("storage/coins/{$this->currency_symbol}");
        }

        return asset("images/coins/{$this->currency_symbol}");
    }

    public function withdrawals()
    {
        return $this->hasMany(Withdrawal::class, 'user_id', 'user_id')
            ->where(function ($query) {
                $query->where('currency_symbol', $this->currency_symbol);
            });
    }

    public function getAccessBalanceAttribute()
    {
        return $this->balance - $this->locked_balance;
    }

    public function lockedBalanceDetails()
    {
        return $this->hasMany(LockedBalanceDetail::class);
    }

}

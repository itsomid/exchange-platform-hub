<?php

namespace App\Models;

use App\Filters\Filterable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property Collection $chains
 */
class Currency extends Model
{
    use filterable, HasFactory;

    public $filterNameSpace = 'App\Filters\CurrencyFilter';

    protected $fillable = [
        'name',
        'persian_name',
        'symbol',
        'logo',
        'price_precision',
        'amount_precision',
        'inter_transfer_enabled',
        'max_auto_withdraw_amount',
        'ref_exchange_withdrawal_enabled',
        'ref_exchange_withdrawal_interval_minutes',
        'ref_exchange_withdrawal_min_count',
        'ref_exchange_withdrawal_aggregation_percent',
    ];

    protected $casts = [
        'ref_exchange_withdrawal_enabled' => 'boolean',
        'ref_exchange_withdrawal_interval_minutes' => 'integer',
        'ref_exchange_withdrawal_min_count' => 'integer',
        'ref_exchange_withdrawal_aggregation_percent' => 'integer',
    ];

    /**
     * Get the effective withdrawal interval for this currency.
     * Falls back to global setting if not set.
     */
    public function getEffectiveWithdrawalInterval(): int
    {
        if ($this->ref_exchange_withdrawal_interval_minutes !== null) {
            return $this->ref_exchange_withdrawal_interval_minutes;
        }
        
        return (int) \App\Models\Setting::getSetting('exchange_withdrawal_period_time', 60);
    }

    /**
     * Get the effective minimum count for this currency.
     * Falls back to global setting if not set.
     */
    public function getEffectiveWithdrawalMinCount(): int
    {
        if ($this->ref_exchange_withdrawal_min_count !== null) {
            return $this->ref_exchange_withdrawal_min_count;
        }
        
        return (int) \App\Models\Setting::getSetting('exchange_withdrawal_period_buy', 1);
    }

    public function chains(): HasMany
    {
        return $this->hasMany(CurrencyChain::class, 'currency_id');
    }

    public function baseMarket(): HasOne
    {
        return $this->hasOne(Market::class, 'base_currency', 'symbol');
    }

    public function quoteMarket(): HasOne
    {
        return $this->hasOne(Market::class, 'quote_currency', 'symbol');
    }
    public function spotBotSetting(): HasOne
    {
        return $this->hasOne(SpotBotSetting::class, 'currency_id');
    }

    public function getExchangePriceAttribute()
    {
        return $this->baseMarket && $this->baseMarket->activeExchangePrice
            ? $this->baseMarket->activeExchangePrice->price
            : 1; // Default to 1 if no exchange rate is found
    }

    public function NodeProviders(): HasMany
    {
        return $this->hasMany(NodeProvider::class, 'currency_id');
    }

    public function wallets()
    {
        return $this->hasMany(Wallet::class, 'currency_symbol');
    }

    public function coinLogo(): string
    {
        if (!$this->logo) {
            return asset('images/coins/default.png');
        }

        $logoPath = storage_path("app/public/coins/{$this->logo}");
        if (file_exists($logoPath)) {
            return asset("storage/coins/{$this->logo}");
        }

        return asset("images/coins/{$this->logo}");
    }

    public function interTransferStatus()
    {
        return (bool) $this->inter_transfer_enabled;
    }
}

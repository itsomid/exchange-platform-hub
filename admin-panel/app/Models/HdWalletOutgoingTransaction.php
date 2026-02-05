<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HdWalletOutgoingTransaction extends Model
{
    protected $fillable = [
        'user_id',
        'currency_symbol',
        'currency_chain_id',
        'amount',
        'transaction_hash',
        'from_address',
        'to_address',
        'block_number',
        'transaction_at',
        'source',
    ];

    protected $casts = [
        'amount' => 'decimal:18',
        'block_number' => 'integer',
        'transaction_at' => 'datetime',
    ];

    /**
     * Get the user (HD Wallet owner) for this transaction.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the currency for this transaction.
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_symbol', 'symbol');
    }

    /**
     * Get the currency chain for this transaction.
     */
    public function currencyChain(): BelongsTo
    {
        return $this->belongsTo(CurrencyChain::class);
    }

    /**
     * Scope to filter by user (HD Wallet Index).
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to filter by currency and chain.
     */
    public function scopeForCurrencyChain($query, string $currencySymbol, int $currencyChainId)
    {
        return $query->where('currency_symbol', $currencySymbol)
                     ->where('currency_chain_id', $currencyChainId);
    }
}

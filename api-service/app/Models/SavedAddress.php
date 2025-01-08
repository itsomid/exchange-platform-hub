<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SavedAddress extends Model
{
    protected $fillable = ['user_id', 'name', 'address', 'currency'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_symbol');
    }

    public function currencyChain(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_chain');
    }
}

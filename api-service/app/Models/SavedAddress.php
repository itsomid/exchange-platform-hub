<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SavedAddress extends Model
{
    protected $fillable = ['user_id', 'name', 'address', 'chain'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    public function currencyChain() : BelongsTo
    {
        return $this->belongsTo(CurrencyChain::class,'chain','chain');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
/**
 * @property string $price
 */
class ExchangePrice extends Model
{
    public function market(): BelongsTo
    {
        return $this->belongsTo(Market::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class ExchangeTransaction extends Model
{
    protected $fillable = [
        'order_id',
        'market',
        'currency_symbol',
        'amount',
        'fee',
        'side',
        'response',
    ];

    protected function casts(): array
    {
        return [
            'response' => 'json',
        ];
    }

    public function otc(): MorphOne
    {
        return $this->morphOne(OTCOrder::class, 'orderable');
    }
}

<?php

namespace App\Models\Bot;

use App\Models\Currency;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BotCurrencyCancellation extends Model
{
    public const STATUS_PENDING = 'PENDING';
    public const STATUS_RUNNING = 'RUNNING';
    public const STATUS_DONE    = 'DONE';
    public const STATUS_FAILED  = 'FAILED';

    public const PRICE_MARKET_SELL = 'market_sell';
    public const PRICE_LIVE        = 'live';

    protected $fillable = [
        'currency_id',
        'admin_id',
        'admin_label',
        'cancel_on_exchange',
        'sell_on_exchange',
        'reason',
        'status',
        'price_source',
        'settlement_price',
        'sell_exchange_order_id',
        'sold_amount',
        'sell_exchange_fee',
        'sell_exchange_fee_currency',
        'sell_error',
        'users_count',
        'orders_count',
        'sell_orders_count',
        'skipped_count',
        'total_amount',
        'total_principal',
        'total_performance_fee',
        'total_refund',
        'details',
        'error',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'cancel_on_exchange'    => 'boolean',
            'sell_on_exchange'      => 'boolean',
            'settlement_price'      => 'decimal:8',
            'sold_amount'           => 'decimal:8',
            'sell_exchange_fee'     => 'decimal:8',
            'total_amount'          => 'decimal:8',
            'total_principal'       => 'decimal:8',
            'total_performance_fee' => 'decimal:8',
            'total_refund'          => 'decimal:8',
            'details'               => 'array',
            'started_at'            => 'datetime',
            'finished_at'           => 'datetime',
        ];
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }
}

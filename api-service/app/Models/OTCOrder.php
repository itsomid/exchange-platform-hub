<?php

namespace App\Models;

use App\Enums\OTCOrderStatusEnum;
use App\Enums\OTCOrderTypeEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OTCOrder extends Model
{
    protected $table = 'otc_orders';

    protected $fillable = ['user_id', 'market_id', 'quantity', 'price', 'fee', 'type', 'status'];

    protected $casts = [
        'type' => OTCOrderTypeEnum::class,
        'status' => OTCOrderStatusEnum::class,
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'otc_order_id');
    }

    public function market(): BelongsTo
    {
        return $this->belongsTo(Market::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

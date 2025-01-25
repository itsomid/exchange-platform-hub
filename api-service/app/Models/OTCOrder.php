<?php

namespace App\Models;

use App\Enums\OTCOrderStatusEnum;
use App\Enums\OTCOrderTypeEnum;
use App\Filters\Filterable;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * @property Carbon             $created_at
 * @property Market             $market
 * @property OTCOrderTypeEnum   $type
 * @property string             $quantity
 * @property string             $price
 * @property string             $fee
 * @property OTCOrderStatusEnum $status
 * @property User               $user
 * @property int                $user_id
 * @property string             $received_amount
 * @property int                $market_id
 */
class OTCOrder extends Model
{
    use Filterable;

    protected $table = 'otc_orders';

    protected $fillable = ['user_id', 'market_id', 'quantity', 'price', 'fee', 'type', 'status', 'exchange_id', 'ref_exchange_description'];

    public string $filterNameSpace = 'App\Filters\OTCOrderFilter';

    protected function casts(): array
    {
        return [
            'type' => OTCOrderTypeEnum::class,
            'status' => OTCOrderStatusEnum::class,
        ];
    }

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

    public function getReceivedAmountAttribute(): string
    {
        return bcsub(
            bcmul($this->quantity, $this->price, config('bitexroom.scale_precision')),
            $this->fee,
            config('bitexroom.scale_precision')
        );
    }

    public function refExchangeTransactions(): MorphOne
    {
        return $this->morphOne(ExchangeTransaction::class, 'orderable');
    }
}

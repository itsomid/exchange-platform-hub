<?php

namespace App\Models;

use App\Enums\OTCOrderStatusEnum;
use App\Enums\OTCOrderTypeEnum;
use App\Filters\Filterable;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
 */
class OTCOrder extends Model
{
    use Filterable;

    protected $table = 'otc_orders';

    protected $fillable = ['user_id', 'market_id', 'quantity', 'price', 'fee', 'type', 'status'];

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
}

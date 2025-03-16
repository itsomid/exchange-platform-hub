<?php

namespace App\Models;

use App\Enums\SpotOrderSideEnum;
use App\Enums\SpotOrderStatusEnum;
use App\Enums\SpotOrderTypeEnum;
use App\Helpers\Math;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int                 $id
 * @property SpotOrderTypeEnum   $type
 * @property SpotOrderSideEnum   $side
 * @property SpotOrderStatusEnum $status
 * @property string              $quantity
 * @property string              $price
 * @property string              $filled_quantity
 * @property int                 $market_id
 * @property int                 $user_id
 */
class SpotOrder extends Model
{
    protected $fillable = [
        'user_id',
        'market_id',
        'side',
        'type',
        'quantity',
        'price',
        'status',
        'filled_quantity',
    ];

    protected function casts(): array
    {
        return [
            'side' => SpotOrderSideEnum::class,
            'type' => SpotOrderTypeEnum::class,
            'status' => SpotOrderStatusEnum::class,
        ];
    }

    public function market(): BelongsTo
    {
        return $this->belongsTo(Market::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getRemindedQuantity(): string
    {
        return Math::sub($this->quantity, $this->filled_quantity);
    }
}

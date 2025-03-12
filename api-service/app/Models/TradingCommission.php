<?php

namespace App\Models;

use App\Enums\TradingRoleEnum;
use Illuminate\Database\Eloquent\Model;

class TradingCommission extends Model
{
    protected $fillable = [
        'spot_trade_id', 'maker_commission_amount', 'maker_commission_percentage', 'taker_commission_amount', 'taker_commission_percentage',
    ];

    protected function casts(): array
    {
        return [
            'role' => TradingRoleEnum::class,
        ];
    }
}

<?php

namespace App\Models\Bot;

use Illuminate\Database\Eloquent\Model;

class BotGlobalSettings extends Model
{
    protected $fillable = [
        'min_deposit_usdt',
        'alpha_weight',
        'default_sell_orders_count',
        'performance_fee_percent',
        'p2p_min_order_value',
        'precheck_floor_mode',
        'transfer_fee_tiers',
        'is_enabled',
        'cancel_sell_on_exchange_enabled',
    ];

    protected function casts(): array
    {
        return [
            'min_deposit_usdt'                => 'decimal:8',
            'alpha_weight'                    => 'decimal:2',
            'performance_fee_percent'         => 'decimal:2',
            'p2p_min_order_value'             => 'decimal:8',
            'precheck_floor_mode'             => 'string',
            'transfer_fee_tiers'              => 'array',
            'is_enabled'                      => 'boolean',
            'cancel_sell_on_exchange_enabled' => 'boolean',
        ];
    }

    public static function current(): static
    {
        return static::firstOrNew([], [
            'min_deposit_usdt'                => 20,
            'alpha_weight'                    => 0.15,
            'default_sell_orders_count'       => 3,
            'performance_fee_percent'         => 22,
            'p2p_min_order_value'             => 5,
            'precheck_floor_mode'             => 'multi',
            'transfer_fee_tiers'              => [],
            'is_enabled'                      => true,
            'cancel_sell_on_exchange_enabled' => true,
        ]);
    }
}

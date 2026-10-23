<?php

namespace App\Models\Bot;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BotGlobalSettings extends Model
{
    use HasFactory;

    protected $fillable = [
        'min_deposit_usdt',
        'alpha_weight',
        'default_sell_orders_count',
        'performance_fee_percent',
        'referral_fee_percent',
        'p2p_min_order_value',
        'precheck_floor_mode',
        'transfer_fee_tiers',
        'is_enabled',
        'cancel_sell_on_exchange_enabled',
        'waiting_sells_auto_place',
        'waiting_safety_margin_percent',
        'waiting_max_per_run',
        'waiting_order_slot_reserve',
        'waiting_rotation_enabled',
        'waiting_rotation_max_per_run',
        'waiting_rotation_min_gap_percent',
    ];

    protected function casts(): array
    {
        return [
            'min_deposit_usdt'                => 'decimal:8',
            'alpha_weight'                    => 'decimal:2',
            'performance_fee_percent'         => 'decimal:2',
            'referral_fee_percent'            => 'decimal:2',
            'p2p_min_order_value'             => 'decimal:8',
            'precheck_floor_mode'             => 'string',
            'transfer_fee_tiers'              => 'array',
            'is_enabled'                      => 'boolean',
            'cancel_sell_on_exchange_enabled' => 'boolean',
            'waiting_sells_auto_place'        => 'boolean',
            'waiting_safety_margin_percent'    => 'decimal:2',
            'waiting_max_per_run'              => 'integer',
            'waiting_order_slot_reserve'       => 'integer',
            'waiting_rotation_enabled'         => 'boolean',
            'waiting_rotation_max_per_run'     => 'integer',
            'waiting_rotation_min_gap_percent' => 'decimal:2',
        ];
    }

    /**
     * Return the single global settings row (creates a default if none exists).
     */
    public static function current(): static
    {
        return static::firstOrNew([], [
            'min_deposit_usdt'                => 20,
            'alpha_weight'                    => 0.15,
            'default_sell_orders_count'       => 3,
            'performance_fee_percent'         => 22,
            'referral_fee_percent'            => 4,
            'p2p_min_order_value'             => 5,
            'precheck_floor_mode'             => 'multi',
            'transfer_fee_tiers'              => [],
            'is_enabled'                      => true,
            'cancel_sell_on_exchange_enabled' => true,
            'waiting_sells_auto_place'        => true,
            'waiting_safety_margin_percent'    => 2,
            'waiting_max_per_run'              => 200,
            'waiting_order_slot_reserve'       => 10,
            'waiting_rotation_enabled'         => true,
            'waiting_rotation_max_per_run'     => 30,
            'waiting_rotation_min_gap_percent' => 10,
        ]);
    }
}

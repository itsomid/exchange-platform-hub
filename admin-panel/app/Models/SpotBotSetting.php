<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpotBotSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'currency_id',
        'is_active',
        'price_interval_seconds',
        'order_margin',
        'buy_orders_count',
        'sell_orders_count',
        'fake_user_id',
        'market_crash_percentage',
        'min_order_size',
        'max_order_size',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function fakeUser()
    {
        return $this->belongsTo(User::class, 'fake_user_id');
    }
}
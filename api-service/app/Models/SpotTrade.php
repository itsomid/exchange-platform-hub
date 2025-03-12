<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 */
class SpotTrade extends Model
{
    protected $fillable = [
        'maker_order_id', 'taker_order_id', 'quantity', 'price',
    ];
}

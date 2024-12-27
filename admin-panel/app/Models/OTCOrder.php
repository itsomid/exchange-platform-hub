<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OTCOrder extends Model
{
    protected $table = 'otc_orders';
    protected $fillable = ['user_id','market_id','quantity','price','fee','type','status'];
}

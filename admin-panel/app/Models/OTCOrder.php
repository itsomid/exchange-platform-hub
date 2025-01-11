<?php

namespace App\Models;

use App\Enums\OTCOrderStatusEnum;
use App\Enums\OTCOrderTypeEnum;
use App\Filters\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;

class OTCOrder extends Model
{
    use Filterable, HasApiTokens, HasFactory;
    public $filterNameSpace = 'App\Filters\OTCOrder';

    protected $table = 'otc_orders';
    protected $fillable = ['user_id','market_id','quantity','price','fee','type','status'];

    protected $casts = [
        'type' => OTCOrderTypeEnum::class,
        'status' => OTCOrderStatusEnum::class,
    ];
    public function transactions()
    {
        return $this->hasMany(Transaction::class,'otc_order_id');
    }

    public function market()
    {
        return $this->belongsTo(Market::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

}

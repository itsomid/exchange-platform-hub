<?php

namespace App\Models;

use App\Enums\OTCOrderStatusEnum;
use App\Enums\OTCOrderTypeEnum;
use App\Enums\RefExchangeSellStatusEnum;
use App\Filters\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;

class OTCOrder extends Model
{
    use Filterable, HasApiTokens, HasFactory, SoftDeletes;
    public $filterNameSpace = 'App\Filters\OTCOrderFilter';

    protected $table = 'otc_orders';
    protected $fillable = ['user_id','market_id','quantity','price','fee','type','status','ref_exchange_sell_status','ref_exchange_description','notes'];

    protected $casts = [
        'type' => OTCOrderTypeEnum::class,
        'status' => OTCOrderStatusEnum::class,
        'ref_exchange_sell_status' => RefExchangeSellStatusEnum::class,
    ];
    protected static function booted()
    {
        static::addGlobalScope('withTotalValue', function ($query) {
            $query->selectRaw('*, (price * quantity) as total_value');
        });
    }
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

    public function exchange()
    {
        return $this->belongsTo(Exchange::class,'exchange_id');
    }

    public function refExchangeTransactions(): MorphOne
    {
        return $this->morphOne(ExchangeTransaction::class, 'orderable');
    }

//    public function getTotalValueAttribute()
//    {
//        return $this->price * $this->quantity;
//    }

}

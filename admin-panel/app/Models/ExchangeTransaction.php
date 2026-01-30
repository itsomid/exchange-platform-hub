<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class ExchangeTransaction extends Model {

    protected $fillable = [
        'order_id',
        'exchange_id',
        'market',
        'currency_symbol',
        'amount',
        'fee',
        'filled_amount',
        'side',
        'response',
        'orderable_id',
        'orderable_type',
    ];

    public function getResponseAttribute($value)
    {
        return json_decode(json_decode($value, true)); // Decode twice
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_symbol','symbol');
    }

    public function exchangeMarket()
    {
        return $this->belongsTo(Market::class, 'currency_symbol','base_currency');
    }

    public function exchange()
    {
        return $this->belongsTo(Exchange::class, 'exchange_id');
    }

    public function getFormattedCreatedAtAttribute()
    {
        return Carbon::createFromTimestampMs($this->response->data->created_at)->timezone('Asia/Tehran')->format('H:i:s Y-m-d');
    }

}

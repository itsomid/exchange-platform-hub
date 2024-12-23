<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Wallet extends Model
{
    protected $fillable = ['user_id','currency_symbol','balance','locked_balance'];
    public function user() : BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_symbol', 'symbol');
    }

    public function coinLogo()
    {
        $logoPath = storage_path("app/public/coins/{$this->currency_symbol}");
        if (file_exists($logoPath)) {
            return asset("storage/coins/{$this->currency_symbol}");
        }

        return asset("images/coins/{$this->currency_symbol}");
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class UserFinancialBlock extends Model
{


    protected $fillable = [
        'user_id',
        'action',
        'reason',
        'restricted_until',
    ];

    public function user()
    {
        return $this->belongsTo(User::class,'user_id');
    }

    public function isExpired()
    {
        return $this->restricted_until < Carbon::now();
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'user_id', 'admin_id', 'wallet_id', 'amount', 'balance', 'type', 'subtype', 'description', 'admin_description', 'status',
    ];
}

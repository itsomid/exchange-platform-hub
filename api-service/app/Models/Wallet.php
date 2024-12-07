<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int    $id
 * @property string $balance
 */
class Wallet extends Model
{
    protected $fillable = [
        'user_id', 'currency_symbol', 'balance', 'locked_balance',
    ];
}

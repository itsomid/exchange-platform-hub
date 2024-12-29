<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $address
 * @property int    $id
 */
class WalletChain extends Model
{
    protected $fillable = [
        'wallet_id', 'currency_chain', 'address',
    ];
}

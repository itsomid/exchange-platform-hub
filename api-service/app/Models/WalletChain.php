<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $public_key
 * @property int    $id
 */
class WalletChain extends Model
{
    protected $fillable = [
        'wallet_id', 'currency_chain', 'public_key',
    ];
}

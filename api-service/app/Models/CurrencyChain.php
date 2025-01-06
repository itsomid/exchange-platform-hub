<?php

namespace App\Models;

use App\Enums\CurrencyBlockChainNameEnum;
use App\Enums\CurrencyChainEnum;
use Illuminate\Database\Eloquent\Model;

/**
 * @property CurrencyBlockChainNameEnum $blockchain_name
 */
class CurrencyChain extends Model
{
    protected $casts = [
        'network_fee' => 'float',
        'exchange_withdrawal_fee' => 'float',
        'chain' => CurrencyChainEnum::class,
        'blockchain_name' => CurrencyBlockChainNameEnum::class,
    ];
}

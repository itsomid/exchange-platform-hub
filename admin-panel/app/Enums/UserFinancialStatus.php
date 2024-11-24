<?php

namespace App\Enums;

enum UserFinancialStatus :string
{
    case WITHDRAW_BLOCK = 'withdraw_block';
    case DEPOSIT_BLOCK = 'deposit_block';
    case TRADE_BLOCK = 'trade_block';
}

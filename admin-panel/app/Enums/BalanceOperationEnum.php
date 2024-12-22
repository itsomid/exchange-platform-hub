<?php

namespace App\Enums;

enum BalanceOperationEnum: string
{
    case INCREASE = 'increase';
    case DECREASE = 'decrease';
}

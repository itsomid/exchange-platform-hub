<?php

namespace App\Enums;

enum BalanceOperationEnum: string
{
    case Increase = 'increase';
    case Decrease = 'decrease';
}

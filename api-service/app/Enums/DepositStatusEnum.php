<?php

namespace App\Enums;

enum DepositStatusEnum: string
{
    case Pending = 'pending';
    case CONFIRMED = 'confirmed';
    case FAILED = 'failed';
}

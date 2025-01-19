<?php

namespace App\Enums;

enum DepositStatusEnum: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case FAILED = 'failed';
}

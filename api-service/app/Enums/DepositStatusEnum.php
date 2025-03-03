<?php

namespace App\Enums;

enum DepositStatusEnum: string
{
    case PENDING = 'pending';
    case TOO_SMALL = 'too_small';
    case CONFIRMED = 'confirmed';
    case FAILED = 'failed';
}

<?php

namespace App\Enums;

enum DepositTypeEnum: string
{
    case MANUAL_ADMIN = 'manual_admin';
    case USER_INITIATED = 'user_initiated';
}

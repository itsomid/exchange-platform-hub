<?php

namespace App\Enums;

enum TransactionSubTypeEnum: string
{
    case MANUAL_ADMIN = 'manual_admin';

    case USER_INITIATED = 'user_initiated';

    case OTC = 'otc';

    case SPOT = 'spot';

    case REFERRAL_INTRODUCER = 'introducer';
    case REFERRAL_FRIEND = 'friend';

    case WITHDRAWAL_FEE = 'withdrawal_fee';

    case OTHER = 'other';
    case COINEX = 'coinex';
}

<?php

namespace App\Enums;

enum TransactionSubTypeEnum : string
{
    case MANUAL_ADMIN = 'manual_admin';

    case USER_INITIATED = 'user_initiated';

    case OTC = 'otc';

    case SPOT = 'spot';

    case REFERRAL_INTRODUCER = 'referral_introducer';
    case REFERRAL_FRIEND = 'referral_friend';

    case WITHDRAWAL_FEE = 'withdrawal_fee';

    case OTHER = 'other';
}

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

    case WITHDRAWAL_EXCHANGE_FEE = 'withdrawal_exchange_fee';
    case WITHDRAWAL_NETWORK_FEE = 'withdrawal_network_fee';
    case HD_WALLET_FEE = 'hd_wallet_fee';

    case OTHER = 'other';
    case COINEX = 'coinex';
}

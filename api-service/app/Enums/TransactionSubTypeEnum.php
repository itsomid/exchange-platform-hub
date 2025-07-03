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
    case EXCHANGE_WITHDRAWAL_FEE = 'exchange_withdrawal_fee';
    case NETWORK_WITHDRAWAL_FEE = 'network_withdrawal_fee';
    case HD_WALLET_FEE = 'hd_wallet_fee';
    case COINEX = 'coinex';
    case REF_EXCHANGE_BUY = 'ref_exchange_buy';
    case REF_EXCHANGE_BUY_FEE = 'ref_exchange_buy_fee';
    case REF_EXCHANGE_WITHDRAWAL = 'ref_exchange_withdrawal';
    case REF_EXCHANGE_WITHDRAWAL_FEE = 'ref_exchange_withdrawal_fee';
    case STOCK = 'stock';
}

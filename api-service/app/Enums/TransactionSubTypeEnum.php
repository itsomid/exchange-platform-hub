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
    case REF_EXCHANGE_SELL = 'ref_exchange_sell';
    case REF_EXCHANGE_SELL_FEE = 'ref_exchange_sell_fee';
    case REF_EXCHANGE_WITHDRAWAL = 'ref_exchange_withdrawal';
    case REF_EXCHANGE_WITHDRAWAL_FEE = 'ref_exchange_withdrawal_fee';
    case STOCK = 'stock';
    case API_SYSTEM = 'api_system';
    case BOT_TRANSFER_IN = 'bot_transfer_in';
    case BOT_TRANSFER_OUT = 'bot_transfer_out';
    case BOT_TRANSFER_FEE = 'bot_transfer_fee';
    case BOT_BUY = 'bot_buy';
    case BOT_SELL = 'bot_sell';
    case BOT_EXCHANGE_FEE = 'bot_exchange_fee';
    case BOT_SPREAD_FEE = 'bot_spread_fee';
    case BOT_PERFORMANCE_FEE = 'bot_performance_fee';
    case BOT_CANCEL_FEE = 'bot_cancel_fee';
    case BOT_NETWORK_FEE = 'bot_network_fee';
    case BOT_REFERRAL_COMMISSION = 'bot_referral_commission';
}

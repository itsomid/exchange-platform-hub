<?php

namespace App\Enums;

enum TransactionSubTypeEnum: string
{
    case MANUAL_ADMIN = 'manual_admin';
    case USER_INITIATED = 'user_initiated';
    case OTC = 'otc';
    case SPOT = 'spot';
    case STOCK = 'stock';
    case REFERRAL_INTRODUCER = 'introducer';
    case REFERRAL_FRIEND = 'friend';
    case COINEX = 'coinex';
    case HOT_WALLET = 'hot_wallet';
    case COLD_WALLET = 'cold_wallet';
    case HD_WALLET_FEE = 'hd_wallet_fee';
    case EXCHANGE_WITHDRAWAL_FEE = 'exchange_withdrawal_fee';
    case NETWORK_WITHDRAWAL_FEE = 'network_withdrawal_fee';
    case REF_EXCHANGE_BUY = 'ref_exchange_buy';
    case REF_EXCHANGE_BUY_FEE = 'ref_exchange_buy_fee';
    case REF_EXCHANGE_SELL = 'ref_exchange_sell';
    case REF_EXCHANGE_SELL_FEE = 'ref_exchange_sell_fee';
    case REF_EXCHANGE_WITHDRAWAL = 'ref_exchange_withdrawal';
    case REF_EXCHANGE_WITHDRAWAL_FEE = 'ref_exchange_withdrawal_fee';
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


    const array TYPE_LABEL = [
        self::MANUAL_ADMIN->value => 'ادمین',
        self::USER_INITIATED->value => 'کاربر',
        self::OTC->value => 'سریع',
        self::SPOT->value => 'اسپات',
        self::REFERRAL_INTRODUCER->value => 'کارمزد معرفی کننده',
        self::REFERRAL_FRIEND->value => 'کارمزد معرفی شونده',
        self::COINEX->value => 'کوینکس',
        self::HOT_WALLET->value => 'هات ولت',
        self::COLD_WALLET->value => 'کلد ولت',
        self::HD_WALLET_FEE->value => 'کارمزد اچ دی ولت',
        self::EXCHANGE_WITHDRAWAL_FEE->value => 'فی برداشت صرافی',
        self::NETWORK_WITHDRAWAL_FEE->value => 'فی برداشت شبکه',
        self::REF_EXCHANGE_BUY->value => 'خرید از صرافی مرجع',
        self::REF_EXCHANGE_BUY_FEE->value => 'فی خرید از صرافی مرجع',
        self::REF_EXCHANGE_SELL->value => 'فروش در صرافی مرجع',
        self::REF_EXCHANGE_SELL_FEE->value => 'فی فروش در صرافی مرجع',
        self::REF_EXCHANGE_WITHDRAWAL->value => 'برداشت از صرافی مرجع',
        self::REF_EXCHANGE_WITHDRAWAL_FEE->value => 'فی برداشت از صرافی مرجع',
        self::STOCK->value => 'سهام',
        self::API_SYSTEM->value => 'سیستم API',
        self::BOT_TRANSFER_IN->value => 'واریز به ربات',
        self::BOT_TRANSFER_OUT->value => 'برداشت از ربات',
        self::BOT_TRANSFER_FEE->value => 'کارمزد انتقال ربات',
        self::BOT_BUY->value => 'خرید ربات',
        self::BOT_SELL->value => 'فروش ربات',
        self::BOT_EXCHANGE_FEE->value => 'کارمزد صرافی ربات',
        self::BOT_SPREAD_FEE->value => 'اسپرد ربات',
        self::BOT_PERFORMANCE_FEE->value => 'کارمزد عملکرد ربات',
        self::BOT_CANCEL_FEE->value => 'کارمزد لغو ربات',
        self::BOT_NETWORK_FEE->value => 'کارمزد شبکه ربات',
    ];

    const array TYPE_COLOR = [
        self::MANUAL_ADMIN->value => 'danger',
        self::USER_INITIATED->value => 'primary',
        self::OTC->value => 'vimeo',
        self::SPOT->value => 'dribble',
        self::REFERRAL_INTRODUCER->value => 'info',
        self::REFERRAL_FRIEND->value => 'info',
        self::COINEX->value => 'info',
        self::HOT_WALLET->value => 'info',
        self::COLD_WALLET->value => 'info',
        self::HD_WALLET_FEE->value => 'info',
        self::EXCHANGE_WITHDRAWAL_FEE->value => 'info',
        self::NETWORK_WITHDRAWAL_FEE->value => 'info',
        self::REF_EXCHANGE_BUY->value => 'info',
        self::REF_EXCHANGE_BUY_FEE->value => 'info',
        self::REF_EXCHANGE_SELL->value => 'info',
        self::REF_EXCHANGE_SELL_FEE->value => 'info',
        self::REF_EXCHANGE_WITHDRAWAL->value => 'info',
        self::REF_EXCHANGE_WITHDRAWAL_FEE->value => 'info',
        self::STOCK->value => 'warning',
        self::API_SYSTEM->value => 'info',
        self::BOT_TRANSFER_IN->value => 'success',
        self::BOT_TRANSFER_OUT->value => 'danger',
        self::BOT_TRANSFER_FEE->value => 'warning',
        self::BOT_BUY->value => 'success',
        self::BOT_SELL->value => 'danger',
        self::BOT_EXCHANGE_FEE->value => 'warning',
        self::BOT_SPREAD_FEE->value => 'warning',
        self::BOT_PERFORMANCE_FEE->value => 'warning',
        self::BOT_CANCEL_FEE->value => 'danger',
        self::BOT_NETWORK_FEE->value => 'warning',
    ];

    public function label(): string
    {
        return self::TYPE_LABEL[$this->value] ?? '';
    }

    /**
     * Get color for the deposit status.
     */
    public function color(): string
    {
        return self::TYPE_COLOR[$this->value] ?? '';
    }
}

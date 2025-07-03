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
    case REF_EXCHANGE_WITHDRAWAL = 'ref_exchange_withdrawal';
    case REF_EXCHANGE_WITHDRAWAL_FEE = 'ref_exchange_withdrawal_fee';


    const array TYPE_LABEL = [
        self::MANUAL_ADMIN->value => 'ادمین',
        self::USER_INITIATED->value => 'کاربر',
        self::OTC->value => 'OTC',
        self::SPOT->value => 'Spot',
        self::REFERRAL_INTRODUCER->value => 'کارمزد معرفی کننده',
        self::REFERRAL_FRIEND->value => 'کارمزد معرفی شونده',
        self::COINEX->value => 'کوینکس',
        self::HOT_WALLET->value => 'هات ولت',
        self::COLD_WALLET->value => 'کلد ولت',
        self::HD_WALLET_FEE->value => 'فی HD wallet',
        self::EXCHANGE_WITHDRAWAL_FEE->value => 'فی برداشت صرافی',
        self::NETWORK_WITHDRAWAL_FEE->value => 'فی برداشت شبکه',
        self::REF_EXCHANGE_BUY->value => 'خرید از صرافی مرجع',
        self::REF_EXCHANGE_BUY_FEE->value => 'فی خرید از صرافی مرجع',
        self::REF_EXCHANGE_WITHDRAWAL->value => 'برداشت از صرافی مرجع',
        self::REF_EXCHANGE_WITHDRAWAL_FEE->value => 'فی برداشت از صرافی مرجع',
        self::STOCK->value => 'سهام',
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
        self::REF_EXCHANGE_WITHDRAWAL->value => 'info',
        self::REF_EXCHANGE_WITHDRAWAL_FEE->value => 'info',
        self::STOCK->value => 'warning',
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

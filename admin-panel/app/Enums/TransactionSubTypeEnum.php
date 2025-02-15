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
    case COINEX = 'coinex';
    case HOT_WALLET = 'hot_wallet';
    case COLD_WALLET = 'cold_wallet';


    const array TYPE_LABEL = [
        self::MANUAL_ADMIN->value => 'ادمین',
        self::USER_INITIATED->value => 'کاربر',
        self::OTC->value => 'OTC',
        self::SPOT->value => 'Spot',
        self::REFERRAL_INTRODUCER->value => 'کارمزد معرفی کننده',
        self::REFERRAL_FRIEND->value => 'کارمزد معرفی شونده',
        self::WITHDRAWAL_FEE->value => 'کارمزد برداشت',
        self::WITHDRAWAL_FEE->value => 'کارمزد برداشت صرافی',
        self::COINEX->value => 'کلد ولت',
        self::HOT_WALLET->value => 'هات ولت',
        self::COLD_WALLET->value => 'کلد ولت',
    ];

    const array TYPE_COLOR = [
        self::MANUAL_ADMIN->value => 'danger',
        self::USER_INITIATED->value => 'primary',
        self::OTC->value => 'vimeo',
        self::SPOT->value => 'dribble',
        self::REFERRAL_INTRODUCER->value => 'info',
        self::REFERRAL_FRIEND->value => 'info',
        self::WITHDRAWAL_FEE->value => 'info',
        self::COINEX->value => 'info',
        self::HOT_WALLET->value => 'info',
        self::COLD_WALLET->value => 'info',
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

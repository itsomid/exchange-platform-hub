<?php

namespace App\Enums;

enum TransactionSubTypeEnum : string
{
    case MANUAL_ADMIN = 'manual_admin';

    case USER_INITIATED = 'user_initiated';

    case OTC = 'otc';

    case SPOT = 'spot';

    case REFERRAL_INTRODUCER = 'introducer';
    case REFERRAL_FRIEND = 'friend';

    case WITHDRAWAL_FEE = 'withdrawal_fee';

    case OTHER = 'other';

    const array TYPE_LABEL = [
        self::MANUAL_ADMIN->value => 'خرید',
        self::USER_INITIATED->value => 'فروش',
        self::OTC->value => 'واریز',
        self::SPOT->value => 'برداشت',
        self::REFERRAL_INTRODUCER->value => 'دعوت از دوستان',
        self::REFERRAL_FRIEND->value => 'کارمزد',
        self::WITHDRAWAL_FEE->value => 'کارمزد',
        self::OTHER->value => 'کارمزد',
//        self::ADMIN_CREDIT->value => 'اعتبار ادمین',
    ];

    const array TYPE_COLOR = [
        self::MANUAL_ADMIN->value => 'خرید',
        self::USER_INITIATED->value => 'فروش',
        self::OTC->value => 'واریز',
        self::SPOT->value => 'برداشت',
        self::REFERRAL_INTRODUCER->value => 'دعوت از دوستان',
        self::REFERRAL_FRIEND->value => 'کارمزد',
        self::WITHDRAWAL_FEE->value => 'کارمزد',
        self::OTHER->value => 'کارمزد',
//        self::ADMIN_CREDIT->value => 'اعتبار ادمین',
    ];
}

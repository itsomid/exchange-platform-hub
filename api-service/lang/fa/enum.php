<?php

return [
    'transaction-type' => [
        \App\Enums\TransactionTypeEnum::DEPOSIT->name => 'واریز',
        \App\Enums\TransactionTypeEnum::WITHDRAWAL->name => 'برداشت',
        \App\Enums\TransactionTypeEnum::BUY->name => 'خرید',
        \App\Enums\TransactionTypeEnum::FEE->name => 'کارمزد',
        \App\Enums\TransactionTypeEnum::SELL->name => 'فروش',
        \App\Enums\TransactionTypeEnum::REFERRAL->name => 'معرفی دوستان',
    ],
    'transaction-status' => [
        \App\Enums\TransactionStatusEnum::SUCCESS->name => 'موفق',
        \App\Enums\TransactionStatusEnum::FAILED->name => 'خطا',
        \App\Enums\TransactionStatusEnum::PENDING->name => 'در انتظار',
    ],
    'deposit-withdrawal' => [
        \App\Enums\WithdrawalStatusEnum::PENDING->name => 'در حال انجام',
        \App\Enums\WithdrawalStatusEnum::FAILED->name => 'ناموفق',
        \App\Enums\WithdrawalStatusEnum::AWAITING_APPROVAL->name => 'در انتظار تایید مدیریت',
        \App\Enums\WithdrawalStatusEnum::COMPLETED->name => 'انجام شده',
        \App\Enums\DepositStatusEnum::PENDING->name => 'در حال انجام',
        \App\Enums\DepositStatusEnum::CONFIRMED->name => 'انجام شده',
    ],
    'otc' => [
        'status' => [
            \App\Enums\OTCOrderStatusEnum::SUCCESS->name => 'موفق',
            \App\Enums\OTCOrderStatusEnum::PENDING->name => 'موفق',
            \App\Enums\OTCOrderStatusEnum::CANCELED->name => 'لغو شده',
        ],
        'type' => [
            \App\Enums\OTCOrderTypeEnum::BUY->name => 'خرید',
            \App\Enums\OTCOrderTypeEnum::SELL->name => 'فروش',
        ],
    ],
];

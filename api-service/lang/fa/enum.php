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
];

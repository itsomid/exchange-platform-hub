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
    'withdrawal' => [
        \App\Enums\WithdrawalStatusEnum::PENDING->name => 'در انتظار',
        \App\Enums\WithdrawalStatusEnum::FAILED->name => 'ناموفق',
        \App\Enums\WithdrawalStatusEnum::QUEUED->name => 'در صف ارسال',
        \App\Enums\WithdrawalStatusEnum::PROCESSING->name => 'در حال پردازش',
        \App\Enums\WithdrawalStatusEnum::AWAITING_APPROVAL->name => 'در انتظار تایید',
        \App\Enums\WithdrawalStatusEnum::COMPLETED->name => 'انجام شده',
        \App\Enums\WithdrawalStatusEnum::REJECTED->name => 'رد شده توسط ادمین',
    ],
    'deposit' => [
        \App\Enums\DepositStatusEnum::PENDING->name => 'در حال انجام',
        \App\Enums\DepositStatusEnum::CONFIRMED->name => 'انجام شده',
        \App\Enums\DepositStatusEnum::TOO_SMALL->name => 'کمتر از حد مجاز',
        \App\Enums\DepositStatusEnum::FAILED->name => 'لغو شده',
    ],
    'otc' => [
        'status' => [
            \App\Enums\OTCOrderStatusEnum::SUCCESS->name => 'موفق',
            \App\Enums\OTCOrderStatusEnum::PENDING->name => 'در حال انجام',
            \App\Enums\OTCOrderStatusEnum::CANCELED->name => 'لغو شده',
        ],
        'type' => [
            \App\Enums\OTCOrderTypeEnum::BUY->name => 'خرید',
            \App\Enums\OTCOrderTypeEnum::SELL->name => 'فروش',
        ],
    ],
    'spot' => [
        'type' => [
            \App\Enums\SpotOrderTypeEnum::MARKET->name => 'سریع',
            \App\Enums\SpotOrderTypeEnum::LIMIT->name => 'تعیین قیمت',
        ],
        'side' => [
            \App\Enums\SpotOrderSideEnum::BUY->name => 'خرید',
            \App\Enums\SpotOrderSideEnum::SELL->name => 'فروش',
        ],
        'status' => [
            \App\Enums\SpotOrderStatusEnum::OPEN->name => 'باز',
            \App\Enums\SpotOrderStatusEnum::COMPLETED->name => 'کامل شده',
            \App\Enums\SpotOrderStatusEnum::CANCELED->name => 'لغو شده',
            \App\Enums\SpotOrderStatusEnum::PARTIALLY_FILLED_CANCELED->name => 'قسمتی تکمیل و لغو شده',
        ],
    ],
    'stock' => [
        'contract-status' => [
            \App\Enums\StockContractStatusEnum::ACTIVE->name => 'فعال',
            \App\Enums\StockContractStatusEnum::CANCELED->name => 'لغو شده',
            \App\Enums\StockContractStatusEnum::SOLD->name => 'فروخته شده',
        ],
        'stock_type' => [
            \App\Enums\StockTypeEnum::NORMAL->name => 'عادی',
            \App\Enums\StockTypeEnum::GIFT->name => 'هدیه',
            \App\Enums\StockTypeEnum::PARTNER->name => 'همکار',
        ],
    ]
];

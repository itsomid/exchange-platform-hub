<?php

return [
    'transaction-type' => [
        \App\Enums\TransactionTypeEnum::DEPOSIT->name => 'deposit',
        \App\Enums\TransactionTypeEnum::WITHDRAWAL->name => 'withdrawal',
        \App\Enums\TransactionTypeEnum::BUY->name => 'buy',
        \App\Enums\TransactionTypeEnum::FEE->name => 'fee',
        \App\Enums\TransactionTypeEnum::SELL->name => 'sell',
        \App\Enums\TransactionTypeEnum::REFERRAL->name => 'referral',
    ],
    'transaction-status' => [
        \App\Enums\TransactionStatusEnum::SUCCESS->name => 'success',
        \App\Enums\TransactionStatusEnum::FAILED->name => 'failed',
        \App\Enums\TransactionStatusEnum::PENDING->name => 'pending',
    ],
    'otc' => [
        'status' => [
            \App\Enums\OTCOrderStatusEnum::SUCCESS->name => 'success',
            \App\Enums\OTCOrderStatusEnum::PENDING->name => 'failed',
            \App\Enums\OTCOrderStatusEnum::CANCELED->name => 'canceled',
        ],
        'type' => [
            \App\Enums\OTCOrderTypeEnum::BUY->name => 'buy',
            \App\Enums\OTCOrderTypeEnum::SELL->name => 'sell',
        ],
    ],
    'stock' => [
        'contract-status' => [
            \App\Enums\StockContractStatusEnum::ACTIVE->name => 'active',
            \App\Enums\StockContractStatusEnum::CANCELED->name => 'canceled',
            \App\Enums\StockContractStatusEnum::SOLD->name => 'sold',
        ],
        'stock_type' => [
            \App\Enums\StockTypeEnum::NORMAL->name => 'normal',
            \App\Enums\StockTypeEnum::GIFT->name => 'gift',
            \App\Enums\StockTypeEnum::PARTNER->name => 'partner',
        ],
    ]
];

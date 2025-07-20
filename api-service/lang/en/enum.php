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
    'deposit-withdrawal' => [
        \App\Enums\WithdrawalStatusEnum::PENDING->name => 'pending',
        \App\Enums\WithdrawalStatusEnum::FAILED->name => 'failed',
        \App\Enums\WithdrawalStatusEnum::AWAITING_APPROVAL->name => 'awaiting_approval',
        \App\Enums\WithdrawalStatusEnum::COMPLETED->name => 'completed',
        \App\Enums\DepositStatusEnum::PENDING->name => 'pending',
        \App\Enums\DepositStatusEnum::CONFIRMED->name => 'confirmed',
        \App\Enums\DepositStatusEnum::TOO_SMALL->name => 'too_small',
        \App\Enums\DepositStatusEnum::FAILED->name => 'canceled',
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

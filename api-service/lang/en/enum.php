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
];

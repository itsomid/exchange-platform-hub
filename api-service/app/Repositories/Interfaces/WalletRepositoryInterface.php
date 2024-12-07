<?php

namespace App\Repositories\Interfaces;

use App\Models\Wallet;

interface WalletRepositoryInterface
{
    public function createOrGetWallet(string $symbol, int $userId): Wallet;

    public function getWalletWithLock(string $symbol, int $userId): Wallet;

    public function updateBalance(string $symbol, int $userId, string $amount): void;
}

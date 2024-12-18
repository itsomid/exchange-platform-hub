<?php

namespace App\Repositories\Interfaces;

use App\Models\Wallet;
use Illuminate\Database\Eloquent\Collection;

interface WalletRepositoryInterface
{
    public function createOrGetWallet(string $symbol, int $userId): Wallet;

    public function getWalletWithLock(string $symbol, int $userId): Wallet;

    public function updateBalance(string $symbol, int $userId, string $amount): void;

    public function getLists(int $getUserId): ?Collection;
}

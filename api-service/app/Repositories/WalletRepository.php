<?php

namespace App\Repositories;

use App\Models\Wallet;
use App\Models\WalletChain;
use App\Repositories\Interfaces\WalletRepositoryInterface;

class WalletRepository implements WalletRepositoryInterface
{
    public function getOne(string $symbol): WalletChain
    {
        return WalletChain::query()
            ->where('chain', $symbol)
            ->first();
    }

    public function createOrGetWallet(string $symbol, int $userId): Wallet
    {
        return Wallet::query()
            ->firstOrCreate([
                'user_id' => $userId,
                'currency_symbol' => $symbol,
            ], [
                'balance' => 0,
                'locked_balance' => 0,
            ]);
    }
}

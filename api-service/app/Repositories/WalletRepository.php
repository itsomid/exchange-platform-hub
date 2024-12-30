<?php

namespace App\Repositories;

use App\Models\Wallet;
use App\Models\WalletChain;
use App\Repositories\Interfaces\WalletRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

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

    public function getWalletWithLock(string $symbol, int $userId): Wallet
    {
        return Wallet::query()->where('currency_symbol', $symbol)
            ->where('user_id', $userId)
            ->lockForUpdate()
            ->first();
    }

    public function updateBalance(string $symbol, int $userId, string $amount): void
    {
        Wallet::query()
            ->where('currency_symbol', $symbol)
            ->where('user_id', $userId)
            ->update([
                'balance' => $amount,
            ]);
    }

    public function getLists(int $getUserId): ?Collection
    {
        return Wallet::query()
            ->where('user_id', $getUserId)
            ->with('exchangePrice')
            ->get();
    }

    public function getListsWithMarket(int $getUserId): ?Collection
    {
        return Wallet::query()
            ->where('user_id', $getUserId)
            ->with('market')
            ->get();
    }

    public function getOrCreateWallet(int $userId, string $symbol): Wallet
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

    public function getOneByCurrency(string $base_currency, int $userId): Wallet
    {
        return Wallet::query()
            ->where('currency_symbol', $base_currency)
            ->where('user_id', $userId)
            ->first();
    }

    public function getOneOrCreateByCurrencyWithLock(string $base_currency, int $userId): Wallet
    {
        return Wallet::query()
            ->lockForUpdate()
            ->firstOrCreate([
                'user_id' => $userId,
                'currency_symbol' => $base_currency,
            ]);
    }
}

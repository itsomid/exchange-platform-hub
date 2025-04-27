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

    public function getListsWithMarket(int $getUserId): ?Collection;

    public function getOrCreateWallet(int $userId, string $symbol): Wallet;

    public function getOneByCurrency(string $base_currency, int $userId): ?Wallet;

    public function getOneOrCreateByCurrencyWithLock(string $base_currency, int $userId): Wallet;

    public function getBitexroomWallet(string $currency): Wallet;

    public function getBitexroomWalletWithLock(string $currency): Wallet;

    public function increaseBalance(int $user_id, string $baseCurrency, string $tradeQuantity);
    public function decreaseBalance(int $user_id, string $baseCurrency, string $tradeQuantity);

    public function decreaseLockedBalance(int $user_id, string $quoteCurrency, string $totalTradeValue);
}

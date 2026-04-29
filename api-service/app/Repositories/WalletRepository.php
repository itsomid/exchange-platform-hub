<?php

namespace App\Repositories;

use App\Helpers\Math;
use App\Models\Currency;
use App\Models\Wallet;
use App\Models\WalletChain;
use App\Repositories\Interfaces\WalletRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class WalletRepository implements WalletRepositoryInterface
{
    public function getOne(string $symbol): WalletChain
    {
        return WalletChain::query()
            ->where('chain', $symbol)
            ->first();
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

    public function getListsPaginated(int $userId, bool $hideZeroBalance, int $page, int $perPage): LengthAwarePaginator
    {
        $wallets = Wallet::query()
            ->where('user_id', $userId)
            ->get(['id', 'currency_symbol', 'balance', 'locked_balance'])
            ->keyBy('currency_symbol');

        $items = $this->getCachedWalletListCurrencies()
            ->map(function (Currency $currency) use ($wallets) {
                $wallet = $wallets->get($currency->symbol);
                $balance = $wallet?->balance ?? '0';
                $exchangePrice = $currency->baseMarket?->exchangePrice?->price;

                return (object) [
                    'currency_symbol' => $currency->symbol,
                    'logo' => $currency->logo,
                    'wallet_id' => $wallet?->id,
                    'balance' => $balance,
                    'locked_balance' => $wallet?->locked_balance ?? '0',
                    'exchange_price' => $exchangePrice,
                    'asset_value' => $exchangePrice
                        ? Math::mul($balance, $exchangePrice)
                        : $balance,
                ];
            })
            ->when($hideZeroBalance, fn ($currencies) => $currencies->filter(
                fn (object $item) => Math::comp($item->balance, '0') === 1
            ))
            ->sort(function (object $left, object $right) {
                $assetValueComparison = Math::comp($right->asset_value, $left->asset_value);

                if ($assetValueComparison !== 0) {
                    return $assetValueComparison;
                }

                return strcmp($left->currency_symbol, $right->currency_symbol);
            })
            ->values();

        return new LengthAwarePaginator(
            $items->forPage($page, $perPage)->values(),
            $items->count(),
            $perPage,
            $page,
            [
                'path' => LengthAwarePaginator::resolveCurrentPath(),
                'pageName' => 'page',
            ]
        );
    }

    public function getListsWithMarket(int $getUserId): ?Collection
    {
        return Wallet::query()
            ->where('user_id', $getUserId)
            ->with('market')
            ->get();
    }

    public function createOrGetWallet(string $symbol, int $userId): Wallet
    {
        return $this->getOrCreateWallet($userId, $symbol);
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

    public function getOneByCurrency(string $base_currency, int $userId): ?Wallet
    {
        return Wallet::query()
            ->where('currency_symbol', $base_currency)
            ->where('user_id', $userId)
            ->first();
    }

    public function getOneByCurrencyWithChain(string $base_currency, string $chainSymbol, int $userId): Wallet
    {
        return Wallet::query()
            ->with(['chains' => fn($q) => $q->where('currency_chain', $chainSymbol)])
            ->where('currency_symbol', $base_currency)
            ->where('user_id', $userId)
            ->first();
    }

    public function getOneOrCreateByCurrencyWithLock(string $base_currency, int $userId): Wallet
    {
        return Wallet::query()
            ->lockForUpdate()
            ->firstOrCreate(
                [
                    'user_id' => $userId,
                    'currency_symbol' => $base_currency,
                ],
                [
                    'balance' => 0,
                    'locked_balance' => 0,
                ]
            );
    }

    public function getBitexroomWallet(string $currency): Wallet
    {
        return Wallet::query()
            ->where('currency_symbol', $currency)
            ->where('user_id', 1)
            ->first();
    }

    public function getBitexroomWalletWithLock(string $currency): Wallet
    {
        return Wallet::query()
            ->where('currency_symbol', $currency)
            ->where('user_id', 1)
            ->lockForUpdate()
            ->first();
    }

    public function increaseBalance(int $user_id, string $baseCurrency, string $tradeQuantity): void
    {
        $wallet = $this->getOrCreateWallet($user_id,$baseCurrency);
        $wallet->balance = Math::add($wallet->balance, $tradeQuantity);
        $wallet->save();
    }
    public function decreaseBalance(int $user_id, string $baseCurrency, string $tradeQuantity): void
    {
        $wallet = $this->getOrCreateWallet($user_id,$baseCurrency);
        $wallet->balance = Math::sub($wallet->balance, $tradeQuantity);
        $wallet->save();
    }

    public function decreaseLockedBalance(int $user_id, string $quoteCurrency, string $totalTradeValue): void
    {
        // Use pessimistic lock to avoid race conditions on concurrent decrements
        $wallet = $this->getWalletWithLock($quoteCurrency, $user_id);
        $newLockedBalance = Math::sub($wallet->locked_balance, $totalTradeValue);

        // Critical validation: prevent negative locked_balance
        if (Math::comp($newLockedBalance, '0') === -1) {
            \Illuminate\Support\Facades\Log::channel('locked-balance-detail')->error(
                "Attempted to set negative locked_balance for user {$user_id}, currency {$quoteCurrency}. Current: {$wallet->locked_balance}, Decrease by: {$totalTradeValue}, Would be: {$newLockedBalance}"
            );

            // Log stack trace to help debug where this is coming from
            \Illuminate\Support\Facades\Log::channel('locked-balance-detail')->error(
                'Stack trace: ' . json_encode(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS), JSON_PRETTY_PRINT)
            );

            throw new \RuntimeException(
                "Cannot decrease locked_balance below zero. User: {$user_id}, Currency: {$quoteCurrency}, Current: {$wallet->locked_balance}, Attempted decrease: {$totalTradeValue}"
            );
        }

        $wallet->locked_balance = $newLockedBalance;
        $wallet->save();
    }

    private function getCachedWalletListCurrencies(): Collection
    {
        return Cache::remember(__CLASS__.'.getListsPaginated.currencies', 3600, fn () => Currency::query()
            ->select(['id', 'symbol', 'logo'])
            ->with([
                'baseMarket:id,base_currency',
                'baseMarket.exchangePrice:id,market_id,price',
            ])
            ->orderBy('symbol')
            ->get());
    }
}

<?php

namespace App\Services\Wallet;

use App\Enums\BalanceOperationEnum;
use App\Exceptions\V1\Wallet\InternalWalletHasProblemException;
use App\Helpers\Math;
use App\Infrastructure\HDWallet\Exceptions\HDDWalletUnavailable;
use App\Infrastructure\HDWallet\Exceptions\HDWalletException;
use App\Infrastructure\HDWallet\HDWallet;
use App\Infrastructure\HDWalletNew\HDWalletFacade;
use App\Repositories\Interfaces\WalletChainRepositoryInterface;
use App\Repositories\Interfaces\WalletRepositoryInterface;
use App\Services\Wallet\DTO\Wallet\GenerateAddressRequestDTO;
use App\Services\Wallet\DTO\Wallet\GenerateAddressResponseDTO;
use App\Services\Wallet\DTO\Wallet\GetOneWalletRequestDTO;
use App\Services\Wallet\DTO\Wallet\GetOneWalletResponseDTO;
use App\Services\Wallet\DTO\Wallet\UpdateBalanceRequestDTO;
use App\Services\Wallet\DTO\Wallet\WalletListsResponseDTO;
use App\Services\Wallet\DTO\Wallet\WalletValueUSDTRequestDTO;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Services\Wallet\DTO\Wallet\WalletValueUSDTResponseDTO;
use Illuminate\Support\Facades\DB;
use Throwable;

class WalletService
{
    public function __construct(
        private readonly WalletRepositoryInterface $walletRepository,
        private readonly WalletChainRepositoryInterface $walletChainRepository,
    ) {}

    public function createWallet(int $userId, string $currencySymbol): void
    {
        $this->walletRepository->getOrCreateWallet($userId, $currencySymbol);
    }

    /**
     * @throws InternalWalletHasProblemException
     * @throws Throwable
     * @throws HDDWalletUnavailable
     */
    public function generateAddress(GenerateAddressRequestDTO $requestDTO): GenerateAddressResponseDTO
    {

        try {
            DB::beginTransaction();
            $wallet = $this->walletRepository->getOrCreateWallet(
                $requestDTO->getUserId(),
                $requestDTO->getCurrency()
            );

            $chain = $this->walletChainRepository->createOrGetChain(
                $wallet->id,
                $requestDTO->getChainSymbol()
            );

            $address = $chain->address;

            $hdWallet = resolve(HDWalletFacade::class);

            // When using the new HD wallet system, always call the new service even if
            // an address already exists in MySQL. This ensures user_addresses in MongoDB
            // stays populated. The new service's getUserAddress() is idempotent:
            // it returns the existing address if found, otherwise generates a new one.
            if (is_null($address) || $hdWallet->isNewSystem()) {
                $blockchainName = $chain->wallet->currency->chains
                    ->where('chain', $chain->currency_chain)->first()->blockchain_name->value;
                $currencySymbol = $chain->wallet->currency->symbol;

                try {
                    $newAddress = $hdWallet->generateAddress(
                        $requestDTO->getUserId(),
                        $blockchainName,
                        $currencySymbol
                    );

                    if ($newAddress !== $address) {
                        $address = $newAddress;
                        $this->walletChainRepository->savePublicKey(
                            $chain->id,
                            $address
                        );
                    }
                } catch (HDWalletException $exception) {
                    report($exception);
                    if (is_null($address)) {
                        // No address at all — cannot continue
                        throw new InternalWalletHasProblemException;
                    }
                    // Address already exists — log the failure but continue with existing address
                }
            }
            DB::commit();
        } catch (Throwable $exception) {
            DB::rollBack();
            report($exception);
            throw $exception;
        }

        return resolve(GenerateAddressResponseDTO::class)
            ->setAddress($address);
    }

    public function updateBalance(UpdateBalanceRequestDTO $requestDTO): bool
    {

        try {
            DB::beginTransaction();

            $wallet = $this->walletRepository
                ->getWalletWithLock($requestDTO->getCurrencySymbol(), $requestDTO->getUserId());

            $balance = $wallet->balance;
            if ($requestDTO->getOperation() === BalanceOperationEnum::Increase) {
                $balance = Math::add($wallet->balance, $requestDTO->getAmount());
            } elseif ($requestDTO->getOperation() === BalanceOperationEnum::Decrease) {
                $balance = Math::sub($wallet->balance, $requestDTO->getAmount());
            }

            $this->walletRepository->updateBalance($requestDTO->getCurrencySymbol(), $requestDTO->getUserId(), $balance);

            DB::commit();

            return true;
        } catch (Throwable $exception) {
            report($exception);
            DB::rollBack();

            return false;
        }
    }

    public function getLists(DTO\Wallet\WalletListsRequestDTO $requestDTO): LengthAwarePaginator
    {
        $paginator = $this->walletRepository->getListsPaginated(
            $requestDTO->getUserId(),
            $requestDTO->getHideZeroBalance(),
            $requestDTO->getPage(),
            $requestDTO->getPerPage(),
        );

        return $paginator->through(function ($item) {
            $balance = $item->balance ?? '0';
            $lockedBalance = $item->locked_balance ?? '0';
            $availableBalance = Math::sub($balance, $lockedBalance);
            $usdtBalance = $item->exchange_price
                ? Math::mul($item->exchange_price, $balance)
                : $balance;
            $usdtLockedBalance = $item->exchange_price
                ? Math::mul($item->exchange_price, $lockedBalance)
                : $lockedBalance;

            return resolve(WalletListsResponseDTO::class)
                ->setId($item->wallet_id)
                ->setCurrency($item->currency_symbol)
                ->setCurrencyLogo($item->logo ?? '')
                ->setBalance($balance)
                ->setLockedBalance($lockedBalance)
                ->setAvailableBalance($availableBalance)
                ->setUsdtBalance($usdtBalance)
                ->setUsdtLockedBalance($usdtLockedBalance);
        });
    }

    public function getWallet(GetOneWalletRequestDTO $requestDTO): GetOneWalletResponseDTO
    {
        $wallet = $this->walletRepository->getOrCreateWallet(
            $requestDTO->getUserId(),
            $requestDTO->getCurrencySymbol()
        );

        return resolve(GetOneWalletResponseDTO::class)
            ->setCurrencySymbol($wallet->currency_symbol)
            ->setBalance($wallet->balance)
            ->setLockedBalance($wallet->locked_balance)
            ->setAvailableBalance($wallet->available_balance ?? 0)
            ->setUsdtBalance(
                $wallet->exchangePrice ?
                    Math::mul($wallet->exchangePrice->price, $wallet->balance) : $wallet->balance
            )
            ->setUsdtLockedBalance(
                $wallet->exchangePrice ?
                    Math::mul($wallet->exchangePrice->price, $wallet->locked_balance) : $wallet->locked_balance
            );
    }
    public function checkBalance(int $userId, string $currencySymbol, float $amount): bool
    {
        try {
            return DB::transaction(function () use ($userId, $currencySymbol, $amount) {
                $wallet = $this->walletRepository->getWalletWithLock($currencySymbol, $userId);
                return $wallet && $wallet->available_balance >= $amount;
            });
        } catch (\Throwable $exception) {
            report($exception);
            return false;
        }
    }

    public function decreaseBalance(int $userId, string $currencySymbol, float $amount): bool
    {
        try {
            return DB::transaction(function () use ($userId, $currencySymbol, $amount) {
                $wallet = $this->walletRepository->getWalletWithLock($currencySymbol, $userId);
                if (!$wallet || $wallet->balance < $amount) {
                    return false;
                }
                $wallet->decrement('balance', $amount);
                return true;
            });
        } catch (\Throwable $exception) {
            report($exception);
            return false;
        }
    }
    public function increaseBalance(int $userId, string $currencySymbol, float $amount): bool
    {
        return DB::transaction(function () use ($userId, $currencySymbol, $amount) {
            $wallet = $this->walletRepository->getWalletWithLock($currencySymbol, $userId);
            $wallet->increment('balance', $amount);
            return true;
        });
    }
    public function walletUSDTValue(WalletValueUSDTRequestDTO $requestDTO)
    {
        $wallets = $this->walletRepository->getLists($requestDTO->getUserId());

        $sumAmount = 0;

        $wallets->map(function ($wallet) use (&$sumAmount) {
            $sumAmount += $wallet->exchangePrice ?
                Math::mul($wallet->exchangePrice->price, $wallet->available_balance, 8) : $wallet->available_balance;
        });

        return resolve(WalletValueUSDTResponseDTO::class)
            ->setAmount($sumAmount);
    }

    private function SetWalletDTO(\App\Models\Wallet $wallet): WalletListsResponseDTO
    {
        return resolve(WalletListsResponseDTO::class)
            ->setId($wallet->id)
            ->setCurrency($wallet->currency_symbol)
            ->setCurrencyLogo($wallet->currency->logo ?? '')
            ->setBalance($wallet->balance ?? 0)
            ->setLockedBalance($wallet->locked_balance ?? 0)
            ->setAvailableBalance($wallet->available_balance ?? 0)
            ->setUsdtBalance(
                $wallet->exchangePrice
                    ? Math::mul($wallet->exchangePrice->price, $wallet->balance ?? 0)
                    : $wallet->balance
            )
            ->setUsdtLockedBalance(
                $wallet->exchangePrice
                    ? Math::mul($wallet->exchangePrice->price, $wallet->locked_balance ?? 0)
                    : $wallet->locked_balance
            );
    }
}

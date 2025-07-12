<?php

namespace App\Services\Wallet;

use App\Enums\BalanceOperationEnum;
use App\Exceptions\V1\Wallet\InternalWalletHasProblemException;
use App\Helpers\Math;
use App\Infrastructure\HDWallet\Exceptions\HDDWalletUnavailable;
use App\Infrastructure\HDWallet\Exceptions\HDWalletException;
use App\Infrastructure\HDWallet\Wallet;
use App\Models\Market;
use App\Repositories\Interfaces\MarketRepositoryInterface;
use App\Repositories\Interfaces\WalletChainRepositoryInterface;
use App\Repositories\Interfaces\WalletRepositoryInterface;
use App\Services\Wallet\DTO\Wallet\GenerateAddressRequestDTO;
use App\Services\Wallet\DTO\Wallet\GenerateAddressResponseDTO;
use App\Services\Wallet\DTO\Wallet\GetOneWalletRequestDTO;
use App\Services\Wallet\DTO\Wallet\GetOneWalletResponseDTO;
use App\Services\Wallet\DTO\Wallet\UpdateBalanceRequestDTO;
use App\Services\Wallet\DTO\Wallet\WalletListsResponseDTO;
use App\Services\Wallet\DTO\Wallet\WalletValueUSDTRequestDTO;
use App\Services\Wallet\DTO\Wallet\WalletValueUSDTResponseDTO;
use Illuminate\Support\Facades\DB;
use Throwable;

class WalletService
{
    public function __construct(
        private readonly WalletRepositoryInterface $walletRepository,
        private readonly WalletChainRepositoryInterface $walletChainRepository,
        private readonly MarketRepositoryInterface $marketRepository,
    ) {}

    public function createWallet(int $userId, string $currencySymbol): void
    {
        $this->walletRepository->createOrGetWallet($currencySymbol, $userId);
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
            $wallet = $this->walletRepository->createOrGetWallet(
                $requestDTO->getCurrency(),
                $requestDTO->getUserId()
            );

            $chain = $this->walletChainRepository->createOrGetChain(
                $wallet->id,
                $requestDTO->getChainSymbol()
            );

            $address = $chain->address;
            if (is_null($address)) {
                //Generate Public Key
                $hdWallet = resolve(Wallet::class);
                $blockchainName = $chain->wallet->currency->chains->where('chain', $chain->currency_chain)->first()->blockchain_name->value; //TODO
                try {
                    $address = $hdWallet->generateAddress(
                        $requestDTO->getUserId(),
                        $blockchainName
                    );
                } catch (HDWalletException $exception) {
                    report($exception);
                    throw new InternalWalletHasProblemException;
                }

                $this->walletChainRepository->savePublicKey(
                    $chain->id,
                    $address
                );
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

    public function getLists(DTO\Wallet\WalletListsRequestDTO $requestDTO): array
    {
        $wallets = $this->walletRepository->getLists($requestDTO->getUserId());
        $markets = $this->marketRepository->getActiveMarket();

        $lists = [];

        $wallets = $wallets->keyBy('currency_symbol');

        foreach ($markets as $market) {
            $wallet = $wallets[$market->base_currency] ?? null;
            $lists[] = $this->SetWalletDTO($wallet, $market);
        }

        //USDT
        $wallet = $wallets['USDT'] ?? null;
        $lists[] = $this->SetWalletDTO($wallet);

        return $lists;
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

    public function SetWalletDTO(?\App\Models\Wallet $wallet, ?Market $market = null): WalletListsResponseDTO
    {
        if (is_null($wallet)) {
            return resolve(WalletListsResponseDTO::class)
                ->setCurrency($market->base_currency)
                ->setBalance('0')
                ->setLockedBalance('0')
                ->setAvailableBalance('0')
                ->setUsdtLockedBalance('0')
                ->setUsdtBalance('0')
                ->setId(null);

        }

        return resolve(WalletListsResponseDTO::class)
            ->setId($wallet->id ?? null)
            ->setCurrency($wallet->currency_symbol ?? $market->base_currency)
            ->setBalance($wallet->balance ?? 0)
            ->setLockedBalance($wallet->locked_balance ?? 0)
            ->setAvailableBalance($wallet->available_balance ?? 0)
            ->setUsdtBalance(
                $wallet && $wallet->exchangePrice ?
                    Math::mul($wallet->exchangePrice->price, $wallet->balance ?? 0) : $wallet->balance
            )
            ->setUsdtLockedBalance(
                $wallet && $wallet->exchangePrice ?
                    Math::mul($wallet->exchangePrice->price, $wallet->locked_balance ?? 0) : $wallet->locked_balance
            );
    }
}
